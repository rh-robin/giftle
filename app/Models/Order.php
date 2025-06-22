<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
   protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'number_of_boxes', 'estimated_budget',
        'currency', 'products_in_bag', 'status', 'campain_name', 'redeem_quantity',
        'multiple_delivery_address', 'campain_type', 'gift_box_type', 'slug',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryAddress()
    {
        return $this->hasOne(DeliveryAddresse::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(BillingAddresse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
