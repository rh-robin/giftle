<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ConversionRate;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CurrencyApiController extends Controller
{
    use ResponseTrait;
    public function getCurrency()
    {
        try {
            $currencies = ConversionRate::select(['id', 'currency', 'conversion_rate'])
                ->latest()
                ->get();



            return $this->sendResponse($currencies->toArray(), 'Currency list retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve currency list: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }
}
