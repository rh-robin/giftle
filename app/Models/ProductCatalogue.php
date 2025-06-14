<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCatalogue extends Model
{
    protected $fillable = [
        'product_id',
        'catalogue_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'catalogue_id' => 'integer',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function catalogue()
    {
        return $this->belongsTo(Catalogue::class);
    }
}
