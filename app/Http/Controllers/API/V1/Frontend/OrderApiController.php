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
use App\Models\Product;
use App\Models\ProductPriceRange;
use App\Services\PaymentService;
use App\Traits\ResponseTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderApiController extends Controller
{
    use ResponseTrait;

    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function store(OrderRequest $request)
    {
        try {
            DB::beginTransaction();

            // Get authenticated user ID
            $user = auth()->user();
            if (!$user) {
                return $this->sendError('Unauthenticated', 'Please provide a valid token', Response::HTTP_UNAUTHORIZED);
            }

            // Calculate total price_gbp
            $priceGbp = 0;
            $numberOfBoxes = $request->input('number_of_boxes', 1);
            $userCurrency = $request->user_currency ?? 'GBP';

            // Initialize conversion rate
            $conversionRate = 1.0; // Default to 1.0 for GBP
            if ($userCurrency !== 'GBP') {
                $gbpRate = ConversionRate::where('currency', 'GBP')->first()->conversion_rate ?? 1.0; // GBP rate relative to USD
                $conversion = ConversionRate::where('currency', $userCurrency)->first();
                if (!$conversion) {
                    throw new Exception("Conversion rate not found for currency {$userCurrency}");
                }
                // Convert GBP to USD (1 / GBP_rate) then to target currency (target_rate)
                $conversionRate = (1 / $gbpRate) * $conversion->conversion_rate;
            }

            // Get product prices and prepare order items data
            $products = $request->input('products', []);
            $orderItemsData = [];

            foreach ($products as $product) {
                $quantity = $product['quantity'] ?? $numberOfBoxes;

                // Validate product exists and has sufficient stock
                $productModel = Product::find($product['product_id']);
                if (!$productModel) {
                    throw new Exception("Product ID {$product['product_id']} not found");
                }
                if ($quantity > $productModel->quantity) {
                    throw new Exception("Order quantity {$quantity} exceeds available stock {$productModel->quantity} for product ID {$product['product_id']}");
                }

                // Get lowest min_quantity to validate
                $lowestMinQuantity = ProductPriceRange::where('product_id', $product['product_id'])
                    ->min('min_quantity');
                if ($lowestMinQuantity === null || $quantity < $lowestMinQuantity) {
                    throw new Exception("Order quantity {$quantity} is less than the minimum required quantity {$lowestMinQuantity} for product ID {$product['product_id']}");
                }

                // Get price range with highest min_quantity <= order quantity
                $priceRange = ProductPriceRange::where('product_id', $product['product_id'])
                    ->where('min_quantity', '<=', $quantity)
                    ->orderBy('min_quantity', 'desc')
                    ->first();

                if (!$priceRange) {
                    throw new Exception("No price range found for product ID {$product['product_id']} with quantity {$quantity}");
                }

                $productPriceGbp = $priceRange->price * $quantity;
                $priceGbp += $productPriceGbp;

                $productPriceInCurrency = $userCurrency === 'GBP'
                    ? $priceRange->price
                    : round($priceRange->price * $conversionRate, 2);

                $orderItemsData[] = [
                    'product_id' => $product['product_id'],
                    'quantity' => $quantity,
                    'product_price_gbp' => $priceRange->price,
                    'product_price_user_currency' => $productPriceInCurrency
                ];
            }

            // Get gift box price
            $giftBoxPriceGbp = 0;
            $giftBoxPriceInCurrency = 0;
            if ($request->gift_box_id && $request->gift_box_type) {
                $giftBox = GiftBox::findOrFail($request->gift_box_id);
                $priceField = match ($request->gift_box_type) {
                    'giftle_branded' => 'giftle_branded_price',
                    'custom_branding' => 'custom_branding_price',
                    'plain' => 'plain_price',
                    default => throw new Exception('Invalid gift box type'),
                };
                $giftBoxPriceGbp = $giftBox->$priceField;
                $priceGbp += $giftBoxPriceGbp;
                $giftBoxPriceInCurrency = $userCurrency === 'GBP'
                    ? $giftBoxPriceGbp
                    : round($giftBoxPriceGbp * $conversionRate, 2);
            }

            // Calculate total price in user currency
            $priceInCurrency = $userCurrency === 'GBP'
                ? $priceGbp
                : round($priceGbp * $conversionRate, 2);

            // Generate unique slug
            $slug = Str::random(8);
            while (Order::where('slug', $slug)->exists()) {
                $slug = Str::random(8);
            }
            Log::info('slug created:', ['order' => $slug]);

            // Create order
            $orderData = $request->only([
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
            $orderData['user_id'] = $user->id;
            $orderData['slug'] = $slug;
            $orderData['price_gbp'] = $priceGbp;
            $orderData['user_currency'] = $userCurrency;
            $orderData['exchange_rate'] = $conversionRate;
            $orderData['price_in_currency'] = $priceInCurrency;
            $orderData['gift_box_price_gbp'] = $giftBoxPriceGbp;
            $orderData['gift_box_price_user_currency'] = $giftBoxPriceInCurrency;

            $order = Order::create($orderData);
            Log::info('Order created:', ['order' => $order]);

            // Store order items with price information
            foreach ($orderItemsData as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'product_price_gbp' => $itemData['product_price_gbp'],
                    'product_price_user_currency' => $itemData['product_price_user_currency']
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

            // Handle payment link if pay_now is true
            $paymentLink = null;
            if ($request->boolean('pay_now')) {
                $checkoutSession = $this->paymentService->createCheckoutSession($priceInCurrency, $userCurrency, $order);
                $paymentLink = $checkoutSession->url;
            }

            // Set invoice link if get_invoice is true
            $invoiceLink = null;
            if ($request->boolean('get_invoice')) {
                $invoiceLink = route('invoice.download', ['slug' => $order->slug]);
            }

            DB::commit();

            // Load relationships for response
            $order->load('orderItems.product', 'billingAddresses', 'deliveryAddresses', 'giftBox');

            return $this->sendResponse([
                'order' => $order,
                'payment_link' => $paymentLink,
                'invoice_link' => $invoiceLink,
            ], 'Order created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }

    public function downloadInvoice($slug)
    {
        $order = Order::where('slug', $slug)->firstOrFail();

        // Authorize the user
        /*if (auth()->check()) {
            $user = auth()->user();
            if ($user->role !== 'admin' && $order->user_id !== $user->id) {
                return $this->sendError('Unauthorized', 'You do not have permission to download this invoice', 403);
            }
        } else {
            return $this->sendError('Unauthenticated', 'Please provide a valid token', Response::HTTP_UNAUTHORIZED);
        }*/

        $pdf = Pdf::loadView('invoices.show', compact('order'));

        return $pdf->download('invoice_order_' . $slug . '.pdf');
    }

    /*============ pending orders ============*/
    public function pendingOrders()
    {
        try {
            $orders = Order::where('status', 'pending')
                ->where('user_id', auth('api')->id())
                ->select([
                    'id',
                    'status',
                    'price_in_currency',
                    'user_currency',
                    'number_of_boxes',
                    'created_at'
                ])
                ->latest()
                ->get();

            if ($orders->isEmpty()) {
                return $this->sendError('No pending orders found', 'No orders available', 200);
            }

            // Prepare response data with formatted price and due_date
            $responseData = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'price' => number_format($order->price_in_currency, 2) . ' ' . $order->user_currency,
                    'quantity' => $order->number_of_boxes,
                    'due_date' => \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d')
                ];
            })->toArray();

            return $this->sendResponse($responseData, 'Pending orders retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve pending orders: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }

    public function viewOrder($id)
    {
        try {
            $order = Order::where('id', $id)
                ->where('user_id', auth('api')->id())
                ->select([
                    'id',
                    'user_id',
                    'name',
                    'email',
                    'phone',
                    'number_of_boxes',
                    'estimated_budget',
                    'products_in_bag',
                    'gift_box_id',
                    'gift_box_type',
                    'status',
                    'campaign_type',
                    'campaign_name',
                    'gift_redeem_quantity',
                    'multiple_delivery_address',
                    'slug',
                    'price_gbp',
                    'user_currency',
                    'exchange_rate',
                    'price_in_currency',
                    'created_at',
                    'updated_at'
                ])
                ->with([
                    'orderItems.product' => function ($query) {
                        $query->select([
                            'id',
                            'name',
                            'thumbnail',
                            'slug'
                        ]);
                    },
                    'giftBox:id,name,giftle_branded_price,custom_branding_price,plain_price',
                    'deliveryAddresses:id,order_id,recipient_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                    'billingAddresses:id,order_id,biller_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town'
                ])
                ->first();

            if (!$order) {
                return $this->sendError('Order not found or not accessible', 'Invalid order ID', 404);
            }

            // Prepare response data with formatted price, due_date, and thumbnail_url
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            // Add thumbnail_url to each product in order_items
            $responseData['order_items'] = collect($responseData['order_items'])->map(function ($item) {
                $item['product']['thumbnail_url'] = $item['product']['thumbnail'] ? asset($item['product']['thumbnail']) : null;
                return $item;
            })->toArray();

            return $this->sendResponse($responseData, 'Order details retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve order details for ID ' . $id . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
