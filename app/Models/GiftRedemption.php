<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftRedemption extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_address_id',
        'selected_items'
    ];

    protected $casts = [
        'selected_items' => 'json'
    ];

    protected $table = 'gift_redemptions';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(DeliveryAddress::class, 'delivery_address_id');
    }
}
