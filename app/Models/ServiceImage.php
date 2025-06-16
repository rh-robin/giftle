<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceImage extends Model
{
    protected $fillable = [
        'images',
        'service_details_id'
    ];

    public function serviceDetails(): BelongsTo
    {
        return $this->belongsTo(ServiceDetails::class, 'service_details_id');
    }

}
