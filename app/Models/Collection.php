<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{

    protected $fillable =
    [
        'name',
        'description',
        'image',
        'slug',
        'status',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_collections', 'collection_id', 'product_id');
    }
    public function getImageAttribute($value)
    {
        return $value ? asset($value) : null;
    }
}
