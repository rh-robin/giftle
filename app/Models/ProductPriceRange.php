<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceRange extends Model
{

    protected $fillable = [
        'product_id',
        'price',
        'min_quantity',
        'max_quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
