<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Microsite;
use App\Models\Order;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignApiController extends Controller
{
    Use ResponseTrait;
    /*============ pending orders ============*/
    public function pendingCampaigns()
    {
        try {
            $orders = Order::where('status', 'pending')
                ->where('user_id', auth('api')->id())
                ->whereNot('campaign_type', null)
                ->select([
                    'id',
                    'status',
                    'price_in_currency',
                    'user_currency',
                    'number_of_boxes',
                    'campaign_type',
                    'created_at',
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
                    'campaign_type' => $order->campaign_type,
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
                ->where('user_id', auth('api')->id())
                ->whereNotNull('campaign_type')
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
                    'billingAddresses:id,order_id,biller_name,email,phone,address_line_1,address_line_2,address_line_3,postal_code,post_town',
                    'microsites' => function ($query) {
                        $query->select([
                            'id',
                            'order_id',
                            'order_item_id',
                            'ask_size',
                            'input_type',
                            'options'
                        ]);
                    }
                ])
                ->first();

            if (!$order) {
                return $this->sendError('Campaign not found or not accessible', 'Invalid campaign ID', 404);
            }

            // Prepare response data with formatted price, due_date, and thumbnail_url
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            // Merge microsite data into order_items and add thumbnail_url
            $responseData['order_items'] = collect($responseData['order_items'])->map(function ($item) use ($order) {
                // Add thumbnail_url to product
                $item['product']['thumbnail_url'] = $item['product']['thumbnail'] ? asset($item['product']['thumbnail']) : null;

                // Find corresponding microsite data
                $microsite = collect($order->microsites)->firstWhere('order_item_id', $item['id']);
                $item['microsite'] = $microsite ? [
                    'ask_size' => $microsite['ask_size'],
                    'input_type' => $microsite['input_type'],
                    'options' => $microsite['options']
                ] : null;

                return $item;
            })->toArray();

            // Remove top-level microsites from response
            unset($responseData['microsites']);

            return $this->sendResponse($responseData, 'Campaign details retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve campaign details for ID ' . $id . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }



    public function updateCampaignName(Request $request, $id)
    {
        try {
            // Validate the order
            $order = Order::where('id', $id)
                ->where('user_id', auth('api')->id())
                ->where('campaign_type', 'microsite')
                ->first();

            if (!$order) {
                return $this->sendError('Campaign not found or not accessible', 'Invalid campaign ID', 404);
            }

            // Validate request data
            $request->validate([
                'campaign_name' => ['required', 'string', 'max:255']
            ]);

            // Update campaign_name in orders table
            $order->update([
                'campaign_name' => $request->campaign_name
            ]);

            // Load updated order for response
            $responseData = $order->toArray();
            $responseData['price'] = number_format($order->price_in_currency, 2) . ' ' . $order->user_currency;
            $responseData['quantity'] = $order->number_of_boxes;
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            return $this->sendResponse($responseData, 'Campaign name updated successfully');
        } catch (Exception $e) {
            Log::error('Failed to update campaign name for ID ' . $id . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
