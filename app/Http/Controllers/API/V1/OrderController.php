<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use ResponseTrait;
    public function pendingOrders()
    {
        try {
            $orders = Order::where('status', 'pending')
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


    /*============ single order details ==============*/
    public function viewOrder($id)
    {
        try {
            $order = Order::where('id', $id)
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
                    'price_usd',
                    'user_currency',
                    'exchange_rate',
                    'price_in_currency',
                    'created_at',
                    'updated_at'
                ])
                ->with([
                    'orderItems.product:id,name,thumbnail,slug',
                    'giftBox:id,name,giftle_branded_price,custom_branding_price,plain_price',
                    'deliveryAddresses:id,order_id,recipient_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                    'billingAddresses:id,order_id,biller_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town'
                ])
                ->first();

            if (!$order) {
                return $this->sendError('Order not found or not pending', 'Invalid order ID', 404);
            }

            // Prepare response data with formatted price and due_date
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            return $this->sendResponse($responseData, 'Order details retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve order details for ID ' . $id . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
