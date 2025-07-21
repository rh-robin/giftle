<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MicrositeItemSize extends Model
{
    protected $table = 'microsite_item_sizes';

    protected $fillable = [
        'delivery_address_id',
        'order_id',
        'order_item_id',
        'size',
    ];

    protected $casts = [
        'size' => 'string',
    ];

    // Relationships
    public function deliveryAddress()
    {
        return $this->belongsTo(DeliveryAddress::class, 'delivery_address_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
