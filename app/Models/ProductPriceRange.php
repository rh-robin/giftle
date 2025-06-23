<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceRange extends Model
{
    protected $table = 'product_price_ranges';

    protected $fillable = [
        'product_id',
        'min_quantity',
        'max_quantity',
        'price',
    ];

    // Relationship
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
