<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\Helper;
use App\Models\Product;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\ProductImage;
use App\Traits\ResponseTrait;
use App\Models\ProductCatalogue;
use App\Models\ProductPriceRange;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProductRequest;
use App\Http\Requests\V1\UpdateProductRequest;

class ProductController extends Controller
{
    use ResponseTrait;

    // List all products
    public function productList()
    {
        try {
            $products = Product::latest()
                ->with(['images:id,product_id,image', 'priceRanges:id,product_id,min_quantity,max_quantity,price'])
                ->get();

            if ($products->isEmpty()) {
                return $this->sendError('No products found', 200);
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

            return $this->sendResponse($responseData, 'Product list retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve product list: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }

    // Create a new product
    public function productCreate(ProductRequest $request)
    {
        DB::beginTransaction();
        try {
            $slug = Str::slug($request->name);
            $slug = $this->makeUniqueSlug($slug);
            $sku = $this->makeUniqueSku($request->name);

            // Handle thumbnail upload
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $thumbnailPath = Helper::fileUpload($file, 'products', $file->getClientOriginalName());
                if (!$thumbnailPath) {
                    throw new Exception('Thumbnail upload failed');
                }
            }

            $product = Product::create([
                'gifting_id' => $request->gifting_id,
                'category_id' => $request->category_id,
                'name' => $request->name,
                'description' => $request->description,
                'thumbnail' => $thumbnailPath,
                'quantity' => $request->quantity,
                'minimum_order_quantity' => $request->minimum_order_quantity,
                'estimated_delivery_time' => $request->estimated_delivery_time,
                'product_type' => $request->product_type,
                'slug' => $slug,
                'sku' => $sku,
                'status' => $request->status ?? 'active',
            ]);

            // Handle additional image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filePath = Helper::fileUpload($file, 'products', $file->getClientOriginalName());
                    if ($filePath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image' => $filePath,
                        ]);
                    } else {
                        throw new Exception('Image upload failed');
                    }
                }
            }

            // Handle price ranges
            if ($request->price_ranges) {
                foreach ($request->price_ranges as $range) {
                    ProductPriceRange::create([
                        'product_id' => $product->id,
                        'min_quantity' => $range['min_quantity'],
                        'max_quantity' => $range['max_quantity'],
                        'price' => $range['price'],
                    ]);
                }
            }

            // Reload product with relationships
            $product->load(['images:id,product_id,image', 'priceRanges:id,product_id,min_quantity,max_quantity,price']);

            // Prepare response data
            $responseData = $product->toArray();
            $responseData['thumbnail'] = $thumbnailPath;
            $responseData['thumbnail_url'] = $thumbnailPath ? asset($thumbnailPath) : null;
            $responseData['images'] = $product->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image' => $image->image,
                    'image_url' => $image->image ? asset($image->image) : null,
                    'product_id' => $image->product_id,
                ];
            })->toArray();
            $responseData['price_ranges'] = $product->priceRanges->map(function ($range) {
                return [
                    'id' => $range->id,
                    'min_quantity' => $range->min_quantity,
                    'max_quantity' => $range->max_quantity,
                    'price' => $range->price,
                    'product_id' => $range->product_id,
                ];
            })->toArray();

            DB::commit();
            return $this->sendResponse($responseData, 'Product created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Product creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    // Update an existing product
    public function productUpdate(ProductRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::find($id);
            if (!$product) {
                DB::rollBack();
                return $this->sendError('Product not found', 404);
            }

            //$slug = Str::slug($request->name);
            //$slug = $this->makeUniqueSlug($slug, $id);
            //$sku = $this->makeUniqueSku($request->name, $id);

            // Handle thumbnail upload
            $thumbnailPath = $product->getRawOriginal('thumbnail');
            if ($request->hasFile('thumbnail')) {
                if ($thumbnailPath) {
                    Helper::fileDelete($thumbnailPath);
                }
                $file = $request->file('thumbnail');
                $thumbnailPath = Helper::fileUpload($file, 'products', $file->getClientOriginalName());
                if (!$thumbnailPath) {
                    throw new Exception('Thumbnail upload failed');
                }
            }

            $product->update([
                'gifting_id' => $request->gifting_id,
                'category_id' => $request->category_id,
                'name' => $request->name,
                'description' => $request->description,
                'thumbnail' => $thumbnailPath,
                'quantity' => $request->quantity,
                'minimum_order_quantity' => $request->minimum_order_quantity,
                'estimated_delivery_time' => $request->estimated_delivery_time,
                'product_type' => $request->product_type,
                'status' => $request->status ?? $product->status,
            ]);

            // Handle image deletions
            if ($request->delete_images) {
                ProductImage::where('product_id', $product->id)
                    ->whereIn('id', $request->delete_images)
                    ->get()
                    ->each(function ($image) {
                        Helper::fileDelete($image->image);
                        $image->delete();
                    });
            }

            // Handle additional image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filePath = Helper::fileUpload($file, 'products', $file->getClientOriginalName());
                    if ($filePath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image' => $filePath,
                        ]);
                    } else {
                        throw new Exception('Image upload failed');
                    }
                }
            }

            // Handle price ranges (delete, update, or create)
            if ($request->price_ranges) {
                foreach ($request->price_ranges as $range) {
                    // Delete if marked
                    if (!empty($range['delete']) && !empty($range['id']) && ProductPriceRange::where('id', $range['id'])->where('product_id', $product->id)->exists()) {
                        ProductPriceRange::where('id', $range['id'])->delete();
                    }
                    // Update if ID exists and not marked for deletion
                    elseif (!empty($range['id']) && ProductPriceRange::where('id', $range['id'])->where('product_id', $product->id)->exists()) {
                        ProductPriceRange::where('id', $range['id'])->update([
                            'min_quantity' => $range['min_quantity'],
                            'max_quantity' => $range['max_quantity'],
                            'price' => $range['price'],
                        ]);
                    }
                    // Create new range if no ID
                    elseif (empty($range['delete'])) {
                        ProductPriceRange::create([
                            'product_id' => $product->id,
                            'min_quantity' => $range['min_quantity'],
                            'max_quantity' => $range['max_quantity'],
                            'price' => $range['price'],
                        ]);
                    }
                }
            }

            // Reload product with relationships
            $product->load(['images:id,product_id,image', 'priceRanges:id,product_id,min_quantity,max_quantity,price']);

            // Response data
            $responseData = $product->toArray();
            $responseData['thumbnail'] = $thumbnailPath;
            $responseData['thumbnail_url'] = $thumbnailPath ? asset($thumbnailPath) : null;
            $responseData['images'] = $product->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image' => $image->image,
                    'image_url' => $image->image ? asset($image->image) : null,
                    'product_id' => $image->product_id,
                ];
            })->toArray();
            $responseData['price_ranges'] = $product->priceRanges->map(function ($range) {
                return [
                    'id' => $range->id,
                    'min_quantity' => $range->min_quantity,
                    'max_quantity' => $range->max_quantity,
                    'price' => $range->price,
                    'product_id' => $range->product_id,
                ];
            })->toArray();

            DB::commit();
            return $this->sendResponse($responseData, 'Product updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Product update failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    // View a single product
    public function productView($id)
    {
        try {
            $product = Product::with(['images:id,product_id,image', 'priceRanges:id,product_id,min_quantity,max_quantity,price'])
                ->select(['id', 'gifting_id', 'category_id', 'name', 'description', 'thumbnail', 'quantity', 'minimum_order_quantity', 'estimated_delivery_time', 'product_type', 'slug', 'sku', 'status'])
                ->find($id);

            if (!$product) {
                return $this->sendError('Product not found', 404);
            }

            // Response data
            $responseData = $product->toArray();
            $responseData['thumbnail'] = $product->getRawOriginal('thumbnail');
            $responseData['thumbnail_url'] = $responseData['thumbnail'] ? asset($responseData['thumbnail']) : null;
            $responseData['images'] = $product->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image' => $image->image,
                    'image_url' => $image->image ? asset($image->image) : null,
                    'product_id' => $image->product_id,
                ];
            })->toArray();
            $responseData['price_ranges'] = $product->priceRanges->map(function ($range) {
                return [
                    'id' => $range->id,
                    'min_quantity' => $range->min_quantity,
                    'max_quantity' => $range->max_quantity,
                    'price' => $range->price,
                    'product_id' => $range->product_id,
                ];
            })->toArray();

            return $this->sendResponse($responseData, 'Product retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve product: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }

    // Delete a product
    public function productDelete($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return $this->sendError('Product not found', 404);
            }

            // Delete thumbnail
            $thumbnailPath = $product->getRawOriginal('thumbnail');
            if ($thumbnailPath) {
                Helper::fileDelete($thumbnailPath);
            }

            // Delete associated images (files and records)
            foreach ($product->images as $image) {
                Helper::fileDelete($image->image);
            }

            // Delete product (cascades to images and price ranges via foreign keys)
            $product->delete();

            return $this->sendResponse(null, 'Product deleted successfully');
        } catch (Exception $e) {
            Log::error('Product deletion failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }

    // Helper method to generate unique slugs
    protected function makeUniqueSlug($slug, $excludeId = null)
    {
        $originalSlug = $slug;
        $count = 1;

        while (Product::where('slug', $slug)
            ->where('id', '!=', $excludeId)
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    // Helper method to generate unique SKU
    protected function makeUniqueSku($name, $excludeId = null)
    {
        $prefix = strtoupper(substr($name, 0, 2));
        $sku = $prefix . mt_rand(10000, 99999);

        while (Product::where('sku', $sku)
            ->where('id', '!=', $excludeId)
            ->exists()) {
            $sku = $prefix . mt_rand(10000, 99999);
        }

        return $sku;
    }
}
