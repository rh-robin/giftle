<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'giftings_id',
        'catalog_id',
        'name',
        'description',
        'price',
        'quantity',
        'minimum_order_quantity',
        'estimated_delivery_time',
        'product_type',
        'slug',
        'sku',
        'status'
    ];

    protected $casts = [
        'product_type' => 'string',
        'status' => 'string'
    ];

    /**
     * Get the category that owns the product.
     */
    public function catalouge()
    {
        return $this->belongsTo(Catalogue::class);
    }

    /**
     * Get the gifting that owns the product.
     */
    public function gifting()
    {
        return $this->belongsTo(Gifting::class, 'giftings_id');
    }

    /**
     * Get all of the images for the product.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the primary image for the product (first image).
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->oldestOfMany();
    }

    /**
     * get the product price range
     */
    public function priceRange()
    {
        return $this->hasMany(ProductPriceRange::class);
    }
}
