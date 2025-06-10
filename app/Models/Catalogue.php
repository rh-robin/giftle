<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalogue extends Model
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
    return $this->hasMany(Product::class);
}
}
