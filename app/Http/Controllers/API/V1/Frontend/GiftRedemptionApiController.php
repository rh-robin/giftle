<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
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
                    $order = Order::find($request->order_id);
                    if ($order) {
                        $productItemCount = $order->items()->where('product_type', 'product')->count();
                        if ($value > $productItemCount) {
                            $fail("The $attribute must not exceed the number of order items with product type 'product' ($productItemCount).");
                        }
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator, 'Validation failed', 422);
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
}
