<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceWhatInclude extends Model
{
   protected $guarded = [];

    public function serviceDetails()
    {
        return $this->belongsTo(ServiceDetails::class);
    }
}
