<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\Category;
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

            $products = $query->get();

            if ($products->isEmpty()) {
                return $this->sendError('No products found', 'No products available', 200);
            }

            // Prepare response data with thumbnail_url and image_url
            $responseData = $products->map(function ($product) {
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
                $data['price_ranges'] = $product->priceRanges->map(function ($range) {
                    return [
                        'id' => $range->id,
                        'min_quantity' => $range->min_quantity,
                        'max_quantity' => $range->max_quantity,
                        'price' => $range->price,
                        'product_id' => $range->product_id,
                    ];
                })->toArray();
                return $data;
            })->toArray();

            return $this->sendResponse($responseData, 'Products retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve products: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }



}
