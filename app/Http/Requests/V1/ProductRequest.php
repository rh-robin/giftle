<?php

namespace App\Http\Requests\V1;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'giftings_id' => 'required|exists:giftings,id',
            'catalog_id' => 'required|exists:catalogues,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:0',
            'minimum_order_quantity' => 'required|integer|min:1',
            'estimated_delivery_time' => 'required|string|max:255',
            'product_type' => ['required', Rule::in(['product', 'bag'])],
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:20048',
            'price_ranges' => 'required|array',
            'price_ranges.*.min_quantity' => 'required_with:price_ranges|integer|min:1',
            'price_ranges.*.max_quantity' => 'required_with:price_ranges|integer|gt:price_ranges.*.min_quantity',
            'price_ranges.*.price' => 'required_with:price_ranges|integer|min:0',
        ];
    }
}

