<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
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
    ];

    // Relationships
    public function gifting()
    {
        return $this->belongsTo(Gifting::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function priceRanges()
    {
        return $this->hasMany(ProductPriceRange::class);
    }
}
