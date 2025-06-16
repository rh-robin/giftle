<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftBox extends Model
{


    protected $fillable = [
        'name',
        'gifte_branded_price',
        'custom_branding_price',
        'plain_price',
        'status',
        'image'
    ];

    protected $casts = [
        'gifte_branded_price' => 'integer',
        'custom_branding_price' => 'integer',
        'plain_price' => 'integer',
    ];

    //image asset
    public function getImageUrlAttribute()
    {
        return $this->image ? asset($this->image) : null;
    }
}
