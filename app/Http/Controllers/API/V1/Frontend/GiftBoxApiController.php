<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\ConversionRate;
use App\Models\GiftBox;
use Exception;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class GiftBoxApiController extends Controller
{
    use ResponseTrait;
    public function index(Request $request)
    {
        try {
            $query = GiftBox::latest();

            // Get currency filter and default to GBP
            $currency = strtoupper($request->query('currency', 'GBP'));
            $gbpRate = ConversionRate::where('currency', 'GBP')->first()->conversion_rate ?? 1.0; // GBP rate relative to USD
            $conversionRate = 1.0; // Default to 1.0 for GBP (no conversion)
            $conversion = ConversionRate::where('currency', $currency)->first();
            if ($currency !== 'GBP') {
                if (!$conversion) {
                    return $this->sendError('Currency not supported', 'Invalid currency', 400);
                }
                $conversionRate = (1 / $gbpRate) * $conversion->conversion_rate;
            }

            $giftBoxes = $query->get();

            if ($giftBoxes->isEmpty()) {
                return $this->sendResponse([], 'No Gift Boxes found', 200);
            }

            $giftBoxes->each(function ($giftBox) use ($conversionRate) {
                // Convert all prices with rounding to 2 decimal places
                $giftBox->giftle_branded_price = round($giftBox->giftle_branded_price * $conversionRate, 2);
                $giftBox->custom_branding_price = round($giftBox->custom_branding_price * $conversionRate, 2);
                $giftBox->plain_price = round($giftBox->plain_price * $conversionRate, 2);

                // Calculate from_price from converted prices
                $giftBox->from_price = min(
                    $giftBox->giftle_branded_price,
                    $giftBox->custom_branding_price,
                    $giftBox->plain_price
                );

                $giftBox->image = asset($giftBox->image);
            });

            return $this->sendResponse($giftBoxes, 'Gift Boxes fetched successfully', 200);
        } catch (Exception $e) {
            Log::error('Failed to retrieve gift boxes: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
