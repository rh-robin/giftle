<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gifting extends Model
{
   protected $fillable = ['name', 'image', 'description', 'slug', 'status'];
    public function products()
    {
        return $this->hasMany(Product::class, 'giftings_id');
    }

    
}
