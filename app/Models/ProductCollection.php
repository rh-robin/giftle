<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCollection extends Model
{
    protected $fillable = [
        'product_id',
        'collection_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'collection_id' => 'integer',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'product_collections', 'product_id', 'collection_id')
            ->using(ProductCollection::class);
    }
}
