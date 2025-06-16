<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'name',
        'description',
        'image',
        'slug',
        'status',
    ];

    public function getImageAttribute($value)
    {
        return $value ? asset($value) : null;
    }
}
