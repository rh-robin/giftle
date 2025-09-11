<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MicrositeController extends Controller
{
    use ResponseTrait;
    public function getRecipientResponses($orderId)
    {
        try {
            // Find the order by order_id, verify campaign type and user ownership
            $order = Order::where('id', $orderId)
                ->where('campaign_type', 'microsite')
                ->select('id', 'campaign_name', 'slug', 'number_of_boxes')
                ->first();

            if (!$order) {
                return $this->sendError('Invalid campaign or not accessible', 'Order not found, not a microsite campaign, or you do not have access', 404);
            }

            // Fetch all delivery addresses with their microsite item sizes
            $deliveryAddresses = DeliveryAddress::where('order_id', $order->id)
                ->with([
                    'micrositeItemSizes' => function ($query) {
                        $query->select([
                            'id',
                            'delivery_address_id',
                            'order_id',
                            'order_item_id',
                            'size'
                        ])->with([
                            'orderItem.product' => function ($query) {
                                $query->select(['id', 'name', 'thumbnail', 'slug']);
                            }
                        ]);
                    }
                ])
                ->select([
                    'id',
                    'order_id',
                    'recipient_name',
                    'email',
                    'phone',
                    'address_line_1',
                    'address_line_2',
                    'address_line_3',
                    'postal_code',
                    'post_town',
                    'created_at'
                ])
                ->get();

            // Prepare response data
            $responseData = [
                'campaign' => [
                    'id' => $order->id,
                    'name' => $order->campaign_name,
                    'slug' => $order->slug,
                    'number_of_boxes' => $order->number_of_boxes
                ],
                'responses' => $deliveryAddresses->map(function ($address) {
                    $selectedSizes = $address->micrositeItemSizes->map(function ($size) {
                        return [
                            'order_item_id' => $size->order_item_id,
                            'size' => $size->size,
                            'product' => $size->orderItem && $size->orderItem->product ? [
                                'id' => $size->orderItem->product->id,
                                'name' => $size->orderItem->product->name,
                                'slug' => $size->orderItem->product->slug,
                                'thumbnail_url' => $size->orderItem->product->thumbnail ? asset($size->orderItem->product->thumbnail) : null
                            ] : null
                        ];
                    })->filter(function ($size) {
                        return !is_null($size['product']);
                    })->values()->toArray();

                    return [
                        'delivery_address_id' => $address->id,
                        'order_id' => $address->order_id,
                        'recipient_name' => $address->recipient_name,
                        'email' => $address->email,
                        'phone' => $address->phone,
                        'address_line_1' => $address->address_line_1,
                        'address_line_2' => $address->address_line_2,
                        'address_line_3' => $address->address_line_3,
                        'postal_code' => $address->postal_code,
                        'post_town' => $address->post_town,
                        'selected_sizes' => $selectedSizes,
                        'created_at' => $address->created_at->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            ];

            return $this->sendResponse(
                $responseData,
                'Recipient responses retrieved successfully',
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve recipient responses for order_id ' . $orderId . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
