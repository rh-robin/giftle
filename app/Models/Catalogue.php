<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalogue extends Model
{
    protected $table = 'catalogues';

    protected $fillable =
    [
        'name',
        'description',
        'image',
        'slug',
        'status',
    ];
}
