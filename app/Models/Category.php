<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $table = 'categories';

    protected $fillable = [
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

    /*public function getImageAttribute($value)
    {
        return $value ? asset($value) : null;
    }*/
}
