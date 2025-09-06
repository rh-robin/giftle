<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\Category;
use App\Models\ConversionRate;
use App\Models\Product;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductApiController extends Controller
{
    use ResponseTrait;
    public function index(Request $request)
    {
        try {
            $query = Product::where('status', 'active')
                ->select([
                    'id',
                    'gifting_id',
                    'category_id',
                    'name',
                    'description',
                    'thumbnail',
                    'quantity',
                    'minimum_order_quantity',
                    'estimated_delivery_time',
                    'product_type',
                    'slug',
                    'sku',
                    'status',
                    'created_at',
                    'updated_at'
                ])
                ->with([
                    'gifting:id,name,image,description,slug,status',
                    'category:id,name,description,image,slug,status',
                    'images:id,product_id,image',
                    'priceRanges:id,product_id,min_quantity,max_quantity,price'
                ])
                ->latest();

            // Filter by category if provided and valid
            $category_id = $request->query('category');
            if ($category_id) {
                if (!Category::where('id', $category_id)->exists()) {
                    return $this->sendError('Category not found', 'Invalid category ID', 404);
                }
                $query->where('category_id', $category_id);
            }

            // Get number_of_boxes filter
            $numberOfBoxes = $request->query('number_of_boxes');
            $numberOfBoxes = $numberOfBoxes ? (int)$numberOfBoxes : null;

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

            $products = $query->get();

            if ($products->isEmpty()) {
                return $this->sendError('No products found', 'No products available', 200);
            }

            // Prepare response data with filters and conversions
            $responseData = $products->map(function ($product) use ($numberOfBoxes, $currency, $conversionRate) {
                $data = $product->toArray();
                $data['thumbnail'] = $product->getRawOriginal('thumbnail');
                $data['thumbnail_url'] = $data['thumbnail'] ? asset($data['thumbnail']) : null;
                $data['images'] = $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image' => $image->image,
                        'image_url' => $image->image ? asset($image->image) : null,
                        'product_id' => $image->product_id,
                    ];
                })->toArray();

                // Convert all price_ranges with consistent rounding to 2 decimal places
                $allPriceRanges = $product->priceRanges->map(function ($range) use ($conversionRate) {
                    return [
                        'id' => $range->id,
                        'min_quantity' => $range->min_quantity,
                        'max_quantity' => $range->max_quantity,
                        'price' => round($range->price * $conversionRate, 2), // Per unit price
                        'product_id' => $range->product_id,
                    ];
                });

                // Calculate from_price with consistent rounding to 2 decimal places
                $data['from_price'] = $allPriceRanges->isEmpty() ? null : round(min($allPriceRanges->pluck('price')->all()), 2);

                // Filter price_ranges based on number_of_boxes for inclusion check
                $validRangeExists = !$numberOfBoxes || $product->priceRanges->contains(function ($range) use ($numberOfBoxes) {
                        return $numberOfBoxes >= $range->min_quantity && $numberOfBoxes <= ($range->max_quantity ?? PHP_INT_MAX);
                    });

                if (!$validRangeExists) {
                    return null; // Skip this product if no valid range
                }

                // Calculate price based on number_of_boxes with 2 decimal places
                $price = null;
                if ($numberOfBoxes) {
                    $matchingRange = $product->priceRanges->first(function ($range) use ($numberOfBoxes) {
                        return $numberOfBoxes >= $range->min_quantity && $numberOfBoxes <= ($range->max_quantity ?? PHP_INT_MAX);
                    });
                    if ($matchingRange) {
                        $price = round($matchingRange->price * $conversionRate, 2);
                    }
                }
                $data['price'] = $price;

                // Include all price_ranges in response
                $data['price_ranges'] = $allPriceRanges->toArray();

                return $data;
            })->filter()->values()->toArray(); // Remove null entries and reindex

            return $this->sendResponse($responseData, 'Products retrieved successfully', 200);
        } catch (Exception $e) {
            Log::error('Failed to retrieve products: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }



}
