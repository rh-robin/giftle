<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'number_gift_packages',
        'estimated_budget',
        'currency',
        'status',
        'products_in_bag',
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
