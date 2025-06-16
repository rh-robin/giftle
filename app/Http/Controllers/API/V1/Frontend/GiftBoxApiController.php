<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\GiftBox;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;

class GiftBoxApiController extends Controller
{
    use ResponseTrait;
    public function index()
    {
        $giftBoxes = GiftBox::latest()->get();

        if ($giftBoxes->isEmpty()) {
            return $this->sendError('No Gift Boxes found', 404);
        }

        $giftBoxes->each(function ($giftBox) {
            $giftBox->from_price = min(
                $giftBox->gifte_branded_price,
                $giftBox->custom_branding_price,
                $giftBox->plain_price
            );
            $giftBox->image = asset($giftBox->image);
        });

        return $this->sendResponse($giftBoxes, 'Gift Boxes fetched successfully');
    }
}
