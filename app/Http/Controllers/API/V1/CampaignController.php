<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    Use ResponseTrait;
    /*============ pending orders ============*/
    public function pendingCampaigns()
    {
        try {
            $orders = Order::where('status', 'pending')
                ->whereNot('campaign_type', null)
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
                return $this->sendError('No pending campaigns found', 'No campaigns available', 200);
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

            return $this->sendResponse($responseData, 'Pending campaigns retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve pending campaigns: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }


    public function viewCampaign($id)
    {
        try {
            $order = Order::where('id', $id)
                ->with([
                    'orderItems.product:id,name,thumbnail,slug',
                    'giftBox:id,name,giftle_branded_price,custom_branding_price,plain_price',
                    'deliveryAddresses:id,order_id,recipient_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                    'billingAddresses:id,order_id,biller_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town'
                ])
                ->first();

            if (!$order) {
                return $this->sendError('Campaign not found or not accessible', 'Invalid campaign ID', 404);
            }

            // Prepare response data with formatted price and due_date
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            return $this->sendResponse($responseData, 'Campaign details retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve campaign details for ID ' . $id . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
