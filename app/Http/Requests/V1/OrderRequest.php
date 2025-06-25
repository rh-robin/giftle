<?php

namespace App\Http\Requests\V1;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'number_of_boxes' => ['nullable', 'integer', 'min:1'],
            'estimated_budget' => ['nullable', 'integer', 'min:0'],
            'products_in_bag' => ['boolean'],
            'gift_box_id' => ['nullable', 'exists:gift_boxes,id'],
            'gift_box_type' => ['nullable', 'in:giftle_branded,custom_branding,plain'],
            'status' => ['required', 'in:pending,action,completed,cancelled,processing'],
            'campaign_type' => ['required', 'in:microsite,gift_redemption'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'gift_redeem_quantity' => ['integer', 'min:0'],
            'multiple_delivery_address' => ['boolean'],
            'user_currency' => ['nullable', 'string', 'max:3', 'exists:conversion_rates,currency'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['nullable', 'integer', 'min:1'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.biller_name' => ['required_if:billing_address,array', 'string', 'max:255'],
            'billing_address.email' => ['required_if:billing_address,array', 'email', 'max:255'],
            'billing_address.phone' => ['required_if:billing_address,array', 'string', 'max:20'],
            'billing_address.address_line_1' => ['required_if:billing_address,array', 'string', 'max:255'],
            'billing_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_address.address_line_3' => ['nullable', 'string', 'max:255'],
            'billing_address.postal_code' => ['required_if:billing_address,array', 'string', 'max:20'],
            'billing_address.post_town' => ['required_if:billing_address,array', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'array', 'required_if:multiple_delivery_address,false'],
            'delivery_address.recipient_name' => ['required_if:delivery_address,array', 'string', 'max:255'],
            'delivery_address.email' => ['required_if:delivery_address,array', 'email', 'max:255'],
            'delivery_address.phone' => ['required_if:delivery_address,array', 'string', 'max:20'],
            'delivery_address.address_line_1' => ['required_if:delivery_address,array', 'string', 'max:255'],
            'delivery_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'delivery_address.address_line_3' => ['nullable', 'string', 'max:255'],
            'delivery_address.postal_code' => ['required_if:delivery_address,array', 'string', 'max:20'],
            'delivery_address.post_town' => ['required_if:delivery_address,array', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'user_currency.exists' => 'The selected user currency is not supported.',
            'gift_box_id.exists' => 'The selected gift box does not exist.',
            'products.*.product_id.exists' => 'One or more product IDs are invalid.',
            'delivery_address.required_if' => 'Delivery address is required when multiple delivery addresses are not enabled.',
        ];
    }
}
