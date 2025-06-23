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
        $id = $this->route('id'); // Get ID for update validation

        return [
            'gifting_id' => ['required', 'exists:giftings,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255', "unique:products,name,{$id}"],
            'description' => ['required', 'string'],
            'thumbnail' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:20480'],
            'quantity' => ['required', 'integer', 'min:0'],
            'minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'estimated_delivery_time' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'in:product,bag'],
            'status' => ['nullable', 'in:active,inactive'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:20480'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer', 'exists:product_images,id'],
            'price_ranges' => ['nullable', 'array'],
            'price_ranges.*.id' => ['nullable', 'integer', 'exists:product_price_ranges,id'],
            'price_ranges.*.min_quantity' => [
                Rule::requiredIf(function () {
                    return $this->has('price_ranges') && !$this->input('price_ranges.*.delete');
                }),
                'integer',
                'min:1',
            ],
            'price_ranges.*.max_quantity' => [
                Rule::requiredIf(function () {
                    return $this->has('price_ranges') && !$this->input('price_ranges.*.delete');
                }),
                'integer',
                'min:1',
                Rule::when($this->has('price_ranges.*.min_quantity'), ['gte:price_ranges.*.min_quantity']),
            ],
            'price_ranges.*.price' => [
                Rule::requiredIf(function () {
                    return $this->has('price_ranges') && !$this->input('price_ranges.*.delete');
                }),
                'integer',
                'min:0',
            ],
            'price_ranges.*.delete' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'The product name already exists.',
            'price_ranges.*.max_quantity.gte' => 'The maximum quantity must be greater than or equal to the minimum quantity.',
        ];
    }
}

