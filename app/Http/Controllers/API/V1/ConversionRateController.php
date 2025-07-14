<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ConversionRate;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConversionRateController extends Controller
{
    use ResponseTrait;
    public function conversionRateStore(Request $request)
    {
        try {
            // Fetch conversion rates from ExchangeRate-API
            $apiKey = config('services.exchangerate.api_key');
            $baseUrl = config('services.exchangerate.base_url');
            $response = Http::get("{$baseUrl}{$apiKey}/latest/USD");

            // Check if API request was successful
            if ($response->failed() || $response->json('result') === 'error') {
                $errorType = $response->json('error-type', 'unknown');
                throw new Exception("Failed to fetch conversion rates: {$errorType}");
            }

            $data = $response->json();
            $conversionRates = $data['conversion_rates'] ?? [];

            if (empty($conversionRates)) {
                throw new Exception('No conversion rates found in API response');
            }

            // Delete all existing conversion rates
            ConversionRate::truncate();

            // Prepare data for storage
            $storedRates = [];
            foreach ($conversionRates as $currency => $rate) {
                // Store the exact rate as provided by the API
                $conversionRate = ConversionRate::create([
                    'currency' => $currency,
                    'conversion_rate' => $rate,
                ]);
                $storedRates[] = [
                    'id' => $conversionRate->id,
                    'currency' => $conversionRate->currency,
                    'conversion_rate' => $conversionRate->conversion_rate,
                    'created_at' => $conversionRate->created_at,
                    'updated_at' => $conversionRate->updated_at,
                ];
            }

            return $this->sendResponse($storedRates, 'Conversion rates stored successfully');
        } catch (Exception $e) {
            Log::error('Failed to store conversion rates: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }

}
