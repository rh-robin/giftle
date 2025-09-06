<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
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
}
