<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'product_price_usd',
        'product_price_user_currency',
        'gift_box_price_usd',
        'gift_box_price_user_currency',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'product_price_usd' => 'decimal:2',
        'product_price_user_currency' => 'decimal:2',
        'gift_box_price_usd' => 'decimal:2',
        'gift_box_price_user_currency' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
