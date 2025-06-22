<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\Helper;
use App\Models\Product;
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

     public function productList()
    {
        $products = Product::with(['catalouge', 'gifting', 'images', 'primaryImage', 'priceRange'])->cursor();
        return $this->sendResponse($products, 'Products List');
    }
    public function productCreate(ProductRequest $request)
    {
        // Create the main product
        $product = Product::create([
            'giftings_id' => intval($request->giftings_id),
            'name' => $request->name,
            'description' => $request->description,
            'price' => intval($request->price),
            'quantity' => intval($request->quantity),
            'minimum_order_quantity' => intval($request->minimum_order_quantity),
            'estimated_delivery_time' => $request->estimated_delivery_time,
            'product_type' => $request->product_type,
            'slug' => $this->createUniqueSlug($request->name),
            'sku' => $this->generateUniqueSku(),
        ]);

        // Store product images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $getName = Str::random(10);
                $path = Helper::fileUpload($image, 'products', $getName);
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                ]);
            }
        }

        // Store price ranges
        if ($request->has('price_ranges')) {
            foreach ($request->price_ranges as $range) {
                ProductPriceRange::create([
                    'product_id' => $product->id,
                    'min_quantity' => $range['min_quantity'],
                    'max_quantity' => $range['max_quantity'],
                    'price' => $range['price']
                ]);
            }
        }

        // Attach collections
        if ($request->has('collections')) {
            $product->collections()->attach($request->collections);
            $product->collections()->updateExistingPivot($request->collections, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Load relationships
        $product->load([
            'images',
            'priceRanges',
            'collections' => function ($query) {
                $query->select('collections.id', 'name', 'description', 'image');
            }
        ]);

        // Hide pivot from collections
        $product->collections->each->makeHidden('pivot');

        return $this->sendResponse($product, 'Product Created Successfully');
    }

    //update product
    public function productUpdate(UpdateProductRequest $request, $id)
    {
        // Find the product or fail
        $product = Product::findOrFail($id);

        // Update the main product
        $product->update([
            'giftings_id' => intval($request->giftings_id),
            'name' => $request->name,
            'description' => $request->description,
            'price' => intval($request->price),
            'quantity' => intval($request->quantity),
            'minimum_order_quantity' => intval($request->minimum_order_quantity),
            'estimated_delivery_time' => $request->estimated_delivery_time,
            'product_type' => $request->product_type,
            'slug' => $this->createUniqueSlug($request->name),
            'sku' => $request->sku ?? $product->sku,
        ]);

        // Handle product images
        if ($request->hasFile('images')) {
            // Add new images
            foreach ($request->file('images') as $image) {
                $path = Helper::fileUpload($image, 'products', $image->getClientOriginalName());
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                ]);
            }
            // If delete_images is true, delete existing images
            if ($request->delete_images) {
                $product->images()->delete();
            }
        }

        // Handle price ranges
        if ($request->has('price_ranges')) {
            // Delete existing price ranges
            $product->priceRanges()->delete();
            foreach ($request->price_ranges as $range) {
                ProductPriceRange::create([
                    'product_id' => $product->id,
                    'min_quantity' => $range['min_quantity'],
                    'max_quantity' => $range['max_quantity'],
                    'price' => $range['price'],
                ]);
            }
        }

        // Sync collections
        if ($request->has('collections')) {
            $product->collections()->sync($request->collections);
            $product->collections()->updateExistingPivot($request->collections, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $product->collections()->detach();
        }
        // Load relationships
        $product->load([
            'images',
            'priceRanges',
            'collections' => function ($query) {
                $query->select('collections.id', 'name', 'description', 'image');
            }
        ]);

        // Hide pivot from collections
        $product->collections->each->makeHidden('pivot');

        return $this->sendResponse($product, 'Product Updated Successfully');
    }

    //delete product
   public function productDelete($id)
    {
        $product = Product::findOrFail($id);

        // Delete associated images and their physical files
        if ($product->images()->exists()) {
            foreach ($product->images as $image) {
                // Delete the physical file
                if (file_exists($image->image)) {
                    unlink($image->image);
                }
                $image->delete();
            }
        }

        // Delete associated price ranges
        $product->priceRanges()->delete();

        // Delete associated collections (pivot table records)
        $product->collections()->detach();

        // Delete the product
        $product->delete();

        return $this->sendResponse([], 'Product and related data deleted successfully');
    }

    // Generate Unique Slug
    private function createUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $count = Product::where('slug', $slug)->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    // Generate Unique SKU
    private function generateUniqueSku()
    {
        do {
            $sku = substr((string) Str::uuid()->getHex(), 0, 8);
        } while (Product::where('sku', $sku)->exists());
        return $sku;
    }
}
