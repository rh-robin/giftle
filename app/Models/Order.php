<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'number_of_boxes',
        'estimated_budget',
        'products_in_bag',
        'gift_box_id',
        'gift_box_type',
        'status',
        'campaign_type',
        'campaign_name',
        'gift_redeem_quantity',
        'multiple_delivery_address',
        'slug',
        'price_usd',
        'user_currency',
        'exchange_rate',
        'price_in_currency',
    ];

    protected $casts = [
        'products_in_bag' => 'boolean',
        'multiple_delivery_address' => 'boolean',
        'gift_box_type' => 'string',
        'status' => 'string',
        'campaign_type' => 'string',
        'price_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'price_in_currency' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function giftBox()
    {
        return $this->belongsTo(GiftBox::class, 'gift_box_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function deliveryAddresses()
    {
        return $this->hasMany(DeliveryAddress::class, 'order_id');
    }

    public function billingAddresses()
    {
        return $this->hasMany(BillingAddress::class, 'order_id');
    }

    public function microsites()
    {
        return $this->hasMany(Microsite::class, 'order_id');
    }
}
