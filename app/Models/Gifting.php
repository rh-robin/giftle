<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gifting extends Model
{
    protected $table = 'giftings';
    protected $fillable = ['name', 'image', 'description', 'slug', 'status'];
    public function products()
    {
        return $this->hasMany(Product::class, 'gifting_id');
    }
    public function getImageAttribute($value)
    {
        return $value ? asset($value) : null;
    }
}
