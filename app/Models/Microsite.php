<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Microsite extends Model
{
    protected $table = 'microsites';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'ask_size',
        'input_type',
        'options',
    ];

    protected $casts = [
        'ask_size' => 'string',
        'input_type' => 'string',
        'options' => 'array',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
