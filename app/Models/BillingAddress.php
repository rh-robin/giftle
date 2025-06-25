<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingAddress extends Model
{
    protected $table = 'billing_addresses';

    protected $fillable = [
        'order_id',
        'biller_name',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'postal_code',
        'post_town',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
