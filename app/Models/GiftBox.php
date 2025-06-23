<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftBox extends Model
{
    protected $table = 'gift_boxes';

    protected $fillable = [
        'name',
        'giftle_branded_price',
        'custom_branding_price',
        'plain_price',
        'status',
        'image'
    ];

    protected $casts = [
        'giftle_branded_price' => 'integer',
        'custom_branding_price' => 'integer',
        'plain_price' => 'integer',
    ];

    /*public function getImageAttribute($value)
    {
        return $value ? asset($value) : null;
    }*/
}
