<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryAddresse extends Model
{
    protected $fillable = [
        'order_id',
        'recipient_name',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'postal_code',
        'post_town'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
