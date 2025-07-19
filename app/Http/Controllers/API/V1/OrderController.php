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
                return $this->sendError('Order not found or not pending', 'Invalid order ID', 404);
            }

            // Prepare response data with formatted price, due_date, and thumbnail_url
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

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
