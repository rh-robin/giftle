<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAddress;
use App\Models\GiftRedemption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GiftRedemptionApiController extends Controller
{
    use ResponseTrait;
    public function setGiftRedeemQuantity(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'gift_redeem_quantity' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    $order = Order::with('orderItems.product')->find($request->order_id);
                    if ($order) {
                        $productItemCount = $order->orderItems->where('product.product_type', 'product')->count();
                        if ($value > $productItemCount) {
                            $fail("The gift redeem quantity must not exceed the number of order items with product type 'product' ($productItemCount).");
                        }
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->toArray(), 'Validation failed', 422);
        }

        // Find the order
        $order = Order::find($request->order_id);

        // Update the gift_redeem_quantity
        $order->gift_redeem_quantity = $request->gift_redeem_quantity;
        $order->save();

        return $this->sendResponse(
            ['order_id' => $order->id, 'gift_redeem_quantity' => $order->gift_redeem_quantity],
            'Gift redeem quantity updated successfully',
            200
        );
    }


    /*============= Recipient Page ==============*/
    public function recipientPage($slug)
    {
        try {
            $order = Order::where('slug', $slug)
                ->where('campaign_type', 'gift_redemption')
                ->select('id', 'gift_redeem_quantity', 'campaign_type', 'campaign_name', 'slug', 'multiple_delivery_address')
                ->with([
                    'orderItems.product' => function ($query) {
                        $query->select([
                            'id',
                            'name',
                            'thumbnail',
                            'slug'
                        ]);
                    }
                ])
                ->first();

            if (!$order) {
                return $this->sendError('Campaign not found', 'Invalid campaign slug', 404);
            }

            // Prepare response data with formatted price, due_date, and thumbnail_url
            $responseData = $order->toArray();
            $responseData['due_date'] = \Carbon\Carbon::parse($order->created_at)->addDays(14)->format('Y-m-d');

            // Merge microsite data into order_items and add thumbnail_url
            $responseData['order_items'] = collect($responseData['order_items'])->map(function ($item) use ($order) {
                // Add thumbnail_url to product
                $item['product']['thumbnail_url'] = $item['product']['thumbnail'] ? asset($item['product']['thumbnail']) : null;
                return $item;
            })->toArray();

            // Remove top-level microsites from response
            unset($responseData['microsites']);

            return $this->sendResponse($responseData, 'Campaign details retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve campaign details for slug ' . $slug . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }


    public function storeRecipientData(Request $request, $slug)
    {
        DB::beginTransaction();
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'selected_items' => 'required|array|min:1',
                'selected_items.*' => 'required|exists:order_items,id',
                'recipient_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address_line_1' => 'nullable|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'address_line_3' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'post_town' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return $this->sendError($validator->errors()->toArray(), 'Validation failed', 422);
            }

            // Find the order by slug and verify campaign type
            $order = Order::where('slug', $slug)
                ->where('campaign_type', 'gift_redemption')
                ->first();

            if (!$order) {
                DB::rollBack();
                return $this->sendError('Invalid campaign', 'Order not found or not a gift redemption campaign', 404);
            }

            // Validate selected_items count against gift_redeem_quantity
            if (count($request->selected_items) > $order->gift_redeem_quantity) {
                DB::rollBack();
                return $this->sendError(
                    ['selected_items' => "Selected items count exceeds gift redeem quantity ({$order->gift_redeem_quantity})"],
                    'Validation failed',
                    422
                );
            }

            // Create delivery address if any address field is provided
            $deliveryAddress = null;
            if ($request->anyFilled(['recipient_name', 'email', 'phone', 'address_line_1', 'address_line_2', 'address_line_3', 'postal_code', 'post_town'])) {
                $deliveryAddress = DeliveryAddress::create([
                    'order_id' => $order->id,
                    'recipient_name' => $request->recipient_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address_line_1' => $request->address_line_1,
                    'address_line_2' => $request->address_line_2,
                    'address_line_3' => $request->address_line_3,
                    'postal_code' => $request->postal_code,
                    'post_town' => $request->post_town,
                ]);
            }

            // Format selected_items as key-value pairs
            $selectedItems = [];
            foreach ($request->selected_items as $index => $orderItemId) {
                $selectedItems["order_item_id_{$index}"] = $orderItemId;
            }

            // Create gift redemption record
            $giftRedemption = GiftRedemption::create([
                'order_id' => $order->id,
                'delivery_address_id' => $deliveryAddress ? $deliveryAddress->id : null,
                'selected_items' => json_encode($selectedItems),
            ]);

            DB::commit();
            return $this->sendResponse(
                [
                    'gift_redemption_id' => $giftRedemption->id,
                    'order_id' => $giftRedemption->order_id,
                    'delivery_address_id' => $giftRedemption->delivery_address_id,
                    'selected_items' => $selectedItems,
                ],
                'Recipient data stored successfully',
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to store recipient data for slug ' . $slug . ': ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }



    public function getRecipientResponses($order_id)
    {
        try {
            // Find the order by order_id, verify campaign type and user ownership
            $order = Order::where('id', $order_id)
                ->where('campaign_type', 'gift_redemption')
                ->where('user_id', auth('api')->id())
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
