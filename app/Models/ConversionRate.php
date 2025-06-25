<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversionRate extends Model
{
    protected $table = 'conversion_rates';

    protected $fillable = [
        'currency',
        'conversion_rate',
    ];
}
