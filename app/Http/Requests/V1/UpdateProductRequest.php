<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'giftings_id' => 'sometimes|exists:giftings,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|integer|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'minimum_order_quantity' => 'sometimes|integer|min:1',
            'estimated_delivery_time' => 'sometimes|string|max:255',
            'product_type' => 'sometimes|in:product,bag',
            'sku' => 'sometimes|string|unique:products,sku,' . $this->route('id'),
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'price_ranges' => 'sometimes|array',
            'price_ranges.*.min_quantity' => 'required_with:price_ranges|integer|min:1',
            'price_ranges.*.max_quantity' => 'required_with:price_ranges|integer|min:1',
            'price_ranges.*.price' => 'required_with:price_ranges|integer|min:0',
            'collections' => 'sometimes|array',
            'collections.*' => 'exists:collections,id',
        ];
    }
}
