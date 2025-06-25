<?php

namespace App\Http\Controllers\API\V1;

use Exception;
use App\Models\Order;
use App\Models\GiftBox;
use App\Models\Product;
use App\Models\Microsite;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use App\Traits\ResponseTrait;
use App\Models\GiftRedemption;
use App\Models\BillingAddress;
use App\Models\DeliveryAddress;
use App\Models\ProductPriceRange;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\V1\OrderRequest;

class CreateOrderApiController extends Controller
{
    use ResponseTrait;
    // create Order
    public function createOrder(OrderRequest $request)
    {
        // Get validated data
        $validated = $request->validated();

        // Start database transaction
        DB::beginTransaction();

        try {
            // Create Order
            $order = Order::create([
                'user_id' => Auth::user()->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'number_of_boxes' => $validated['number_of_boxes'],
                'estimated_budget' => $validated['estimated_budget'],
                'currency' => $validated['currency'],
                'products_in_bag' => $validated['products_in_bag'],
                'status' => 'pending',
                'campain_name' => $validated['campain_name'],
                'redeem_quantity' => $validated['redeem_quantity'],
                'multiple_delivery_address' => $validated['multiple_delivery_address'],
                'campain_type' => $validated['campain_type'],
                'gift_box_type' => $validated['gift_box_type'],
                'slug' => Str::slug($validated['name'] . '-' . time()),
            ]);

            // Create Delivery Address
            $deliveryAddress = DeliveryAddress::create([
                'order_id' => $order->id,
                'recipient_name' => $validated['delivery_address']['recipient_name'],
                'email' => $validated['delivery_address']['email'],
                'phone' => $validated['delivery_address']['phone'],
                'address_line_1' => $validated['delivery_address']['address_line_1'],
                'address_line_2' => $validated['delivery_address']['address_line_2'] ?? null,
                'address_line_3' => $validated['delivery_address']['address_line_3'] ?? null,
                'postal_code' => $validated['delivery_address']['postal_code'],
                'post_town' => $validated['delivery_address']['post_town'],
            ]);

            // Create Billing Address
            $billingAddress = BillingAddress::create([
                'order_id' => $order->id,
                'recipient_name' => $validated['billing_address']['recipient_name'],
                'email' => $validated['billing_address']['email'],
                'phone' => $validated['billing_address']['phone'],
                'address_line_1' => $validated['billing_address']['address_line_1'],
                'address_line_2' => $validated['billing_address']['address_line_2'] ?? null,
                'address_line_3' => $validated['billing_address']['address_line_3'] ?? null,
                'postal_code' => $validated['billing_address']['postal_code'],
                'post_town' => $validated['billing_address']['post_town'],
            ]);

            // Calculate total price based on gift box type and product quantities
            $giftBox = GiftBox::findOrFail($validated['gift_box_id']);
            $giftBoxPrice = match ($validated['gift_box_type']) {
                'gifte_branded' => $giftBox->gifte_branded_price,
                'custom_branding' => $giftBox->custom_branding_price,
                'plain' => $giftBox->plain_price,
                default => 0,
            };
            $totalPrice = $giftBoxPrice * $validated['number_of_boxes'];

            // Create Order Items and calculate product prices
            $orderItems = [];
            foreach ($validated['products'] as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $quantity = $productData['quantity'];

                // Check product price range
                $priceRange = ProductPriceRange::where('product_id', $product->id)
                    ->where('min_quantity', '<=', $quantity)
                    ->where('max_quantity', '>=', $quantity)
                    ->first();

                $productPrice = $priceRange ? $priceRange->price : $product->price;
                $totalPrice += $productPrice * $quantity;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);

                $orderItems[] = [
                    'order_item_id' => $orderItem->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $productPrice,
                ];
            }

            // Update order with total price
            $order->update(['estimated_budget' => $totalPrice]);

            // Handle campaign type
            if ($validated['campain_type'] === 'gift_redemption') {
                GiftRedemption::create([
                    'order_id' => $order->id,
                    'dilivery_address_id' => $deliveryAddress->id,
                    'selected_items' => json_encode($validated['gift_redemption']['selected_items']),
                ]);
            } elseif ($validated['campain_type'] === 'microsite') {
                Microsite::create([
                    'order_id' => $order->id,
                    'order_item_id' => $orderItems[0]['order_item_id'],
                    'ask_size' => $validated['microsite']['ask_size'],
                    'input_type' => $validated['microsite']['input_type'],
                    'options' => json_encode($validated['microsite']['options']),
                ]);
            }

            // Commit transaction
            DB::commit();

            // Return success response
            return response()->json([
                'message' => 'Order created successfully',
                'order_id' => $order->id,
                'total_price' => $totalPrice,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
