<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Microsite extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'ask_size',
        'input_type',
        'options',
    ];

    protected $casts = [
        'options' => 'array'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }


}
