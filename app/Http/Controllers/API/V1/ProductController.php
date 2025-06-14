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

class ProductController extends Controller
{
    use ResponseTrait;

    public function ProductList()
    {
        $products = Product::with(['catalouge', 'gifting', 'images', 'primaryImage', 'priceRange'])->cursor();
        return $this->sendResponse($products, 'Products List');
    }

    public function ProductCreate(ProductRequest $request)
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
            'slug' => $this->createUniqueSlug($request->name), // Generate a unique slug
            'sku' => $this->generateUniqueSku(), // Generate a unique SKU
        ]);

        // Store product images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = Helper::fileUpload($image, 'products', $image->getClientOriginalName());
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
        if ($request->has('catalog_ids')) {
            foreach ($request->catalog_ids as $catalog_id) {
                DB::table('product_catalogues')->insert([
                    'product_id' => $product->id,
                    'catalogue_id' => intval($catalog_id),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $product->load([
            'images',
            'priceRange',
            'catalogues' => function ($query) {
                $query->select('catalogues.id', 'name', 'description', 'image');
            }
        ]);

        // Hide pivot from catalogues
        $product->catalogues->each->makeHidden('pivot');
        return $this->sendResponse($product, 'Product Created Successfully');
    }

    // Generate Unique Slug
    private function createUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $count = Product::where('slug', $slug)->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }
    //generate SKU
    private function generateUniqueSku()
    {
        do {
            $sku = substr((string) Str::uuid()->getHex(), 0, 8);
        } while (Product::where('sku', $sku)->exists());
        return $sku;
    }
}
