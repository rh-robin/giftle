<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'giftings_id',
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
    public function thumbnailImage()
    {
        return $this->hasOne(ProductImage::class)->oldestOfMany();
    }
    /**
     * get the product price range
     */
    public function priceRanges()
    {
        return $this->hasMany(ProductPriceRange::class);
    }

    // get the product catalogues
    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'product_collections', 'product_id', 'collection_id');
    }
     public function getImageAttribute($value)
    {
        return $value ? asset($value) : null;
    }
}
