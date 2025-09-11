<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\GiftRedemption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GiftRedemptionController extends Controller
{
    use ResponseTrait;
    public function getRecipientResponses($order_id)
    {
        try {
            // Find the order by order_id, verify campaign type and user ownership
            $order = Order::where('id', $order_id)
                ->where('campaign_type', 'gift_redemption')
                ->select('id', 'campaign_name', 'slug')
                ->first();

            if (!$order) {
                return $this->sendError('Invalid campaign or not accessible', 'Order not found, not a gift redemption campaign, or you do not have access', 404);
            }

            // Fetch all gift redemptions for the order
            $redemptions = GiftRedemption::where('order_id', $order->id)
                ->with([
                    'deliveryAddress' => function ($query) {
                        $query->select([
                            'id',
                            'order_id',
                            'recipient_name',
                            'email',
                            'phone',
                            'address_line_1',
                            'address_line_2',
                            'address_line_3',
                            'postal_code',
                            'post_town'
                        ]);
                    }
                ])
                ->get();

            // Prepare response data
            $responseData = [
                'campaign' => [
                    'id' => $order->id,
                    'name' => $order->campaign_name,
                    'slug' => $order->slug
                ],
                'redemptions' => $redemptions->map(function ($redemption) {
                    // Decode selected_items JSON
                    $selectedItems = json_decode($redemption->selected_items, true) ?? [];

                    // Fetch product details for selected items
                    $items = collect($selectedItems)->map(function ($orderItemId, $key) {
                        $orderItem = OrderItem::where('id', $orderItemId)
                            ->with(['product' => function ($query) {
                                $query->select('id', 'name', 'thumbnail', 'slug');
                            }])
                            ->first();

                        return $orderItem ? [
                            'order_item_id' => $orderItem->id,
                            'product' => [
                                'id' => $orderItem->product->id,
                                'name' => $orderItem->product->name,
                                'slug' => $orderItem->product->slug,
                                'thumbnail_url' => $orderItem->product->thumbnail ? asset($orderItem->product->thumbnail) : null
                            ]
                        ] : null;
                    })->filter()->values()->toArray();

                    return [
                        'gift_redemption_id' => $redemption->id,
                        'order_id' => $redemption->order_id,
                        'delivery_address' => $redemption->deliveryAddress ? [
                            'id' => $redemption->deliveryAddress->id,
                            'recipient_name' => $redemption->deliveryAddress->recipient_name,
                            'email' => $redemption->deliveryAddress->email,
                            'phone' => $redemption->deliveryAddress->phone,
                            'address_line_1' => $redemption->deliveryAddress->address_line_1,
                            'address_line_2' => $redemption->deliveryAddress->address_line_2,
                            'address_line_3' => $redemption->deliveryAddress->address_line_3,
                            'postal_code' => $redemption->deliveryAddress->postal_code,
                            'post_town' => $redemption->deliveryAddress->post_town
                        ] : null,
                        'selected_items' => $items,
                        'created_at' => $redemption->created_at->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            ];

            return $this->sendResponse(
                $responseData,
                'Recipient responses retrieved successfully',
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve recipient responses for order_id ' . $order_id . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
