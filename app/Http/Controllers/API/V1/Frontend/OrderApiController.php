<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\OrderRequest;
use App\Models\BillingAddress;
use App\Models\ConversionRate;
use App\Models\DeliveryAddress;
use App\Models\GiftBox;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductPriceRange;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderApiController extends Controller
{
    use ResponseTrait;
    public function store(OrderRequest $request)
    {
        try {
            DB::beginTransaction();

            // Calculate total price_usd
            $priceUsd = 0;
            $numberOfBoxes = $request->input('number_of_boxes', 1); // Default to 1 if not provided

            // Get product prices
            $products = $request->input('products', []);
            foreach ($products as $product) {
                $quantity = $product['quantity'] ?? $numberOfBoxes; // Use number_of_boxes if quantity not provided
                $priceRange = ProductPriceRange::where('product_id', $product['product_id'])
                    ->where('min_quantity', '<=', $quantity)
                    ->where('max_quantity', '>=', $quantity)
                    ->first();

                if (!$priceRange) {
                    throw new Exception("No price range found for product ID {$product['product_id']} with quantity {$quantity}");
                }

                $priceUsd += $priceRange->price * $quantity;
            }

            // Get gift box price
            if ($request->gift_box_id && $request->gift_box_type) {
                $giftBox = GiftBox::findOrFail($request->gift_box_id);
                $priceField = match ($request->gift_box_type) {
                    'giftle_branded' => 'giftle_branded_price',
                    'custom_branding' => 'custom_branding_price',
                    'plain' => 'plain_price',
                    default => throw new Exception('Invalid gift box type'),
                };
                $priceUsd += $giftBox->$priceField;
            }

            // Get conversion rate
            $userCurrency = $request->user_currency ?? 'USD';
            $conversionRate = $userCurrency === 'USD' ? 1 : null;
            $priceInCurrency = $priceUsd;

            if ($userCurrency !== 'USD') {
                $conversion = ConversionRate::where('currency', $userCurrency)->first();
                if (!$conversion) {
                    throw new Exception("Conversion rate not found for currency {$userCurrency}");
                }
                $conversionRate = $conversion->conversion_rate;
                $priceInCurrency = round($priceUsd * $conversionRate, 2);
            }

            // Generate unique slug
            $slug = Str::random(8);
            while (Order::where('slug', $slug)->exists()) {
                $slug = Str::random(8);
            }
            Log::info('slug created:', ['order' => $slug]);

            // Create order
            $orderData = $request->only([
                'user_id',
                'name',
                'email',
                'phone',
                'number_of_boxes',
                'estimated_budget',
                'products_in_bag',
                'gift_box_id',
                'gift_box_type',
                'campaign_type',
                'campaign_name',
                'gift_redeem_quantity',
                'multiple_delivery_address',
            ]);
            $orderData['slug'] = $slug;
            $orderData['price_usd'] = $priceUsd;
            $orderData['user_currency'] = $userCurrency;
            $orderData['exchange_rate'] = $conversionRate;
            $orderData['price_in_currency'] = $priceInCurrency;

            $order = Order::create($orderData);
            Log::info('Order created:', ['order' => $order]);

            // Store order items
            foreach ($products as $product) {
                $quantity = $product['quantity'] ?? $numberOfBoxes;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $quantity,
                ]);
            }

            // Store addresses
            if ($request->has('billing_address')) {
                BillingAddress::create(array_merge(
                    ['order_id' => $order->id],
                    $request->billing_address
                ));
            }

            if (!$request->multiple_delivery_address && $request->has('delivery_address')) {
                DeliveryAddress::create(array_merge(
                    ['order_id' => $order->id],
                    $request->delivery_address
                ));
            }

            DB::commit();

            // Load relationships for response
            $order->load('orderItems.product', 'billingAddresses', 'deliveryAddresses', 'giftBox');

            return $this->sendResponse($order, 'Order created successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order: ' . $e->getMessage());
            return $this->sendError($e->getMessage(),'Something went wrong', 500);
        }
    }
}
