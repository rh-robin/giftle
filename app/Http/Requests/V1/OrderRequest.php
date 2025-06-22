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
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'number_of_boxes' => 'required|integer|min:1',
            'estimated_budget' => 'required|integer|min:0',
            'currency' => 'required|string|in:USD,GBP,EUR',
            'products_in_bag' => 'required|boolean',
            'campain_name' => 'nullable|string|max:255',
            'redeem_quantity' => 'required|integer|min:0',
            'multiple_delivery_address' => 'required|in:yes,no',
            'campain_type' => 'required|in:microsite,gift_redemption',
            'gift_box_id' => 'required|exists:gift_boxes,id',
            'gift_box_type' => 'required|in:gifte_branded,custom_branding,plain',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'delivery_address' => ['required', 'array'],
            'delivery_address.recipient_name' => 'required|string|max:255',
            'delivery_address.email' => 'required|email|max:255',
            'delivery_address.phone' => 'required|string|max:20',
            'delivery_address.address_line_1' => 'required|string|max:255',
            'delivery_address.address_line_2' => 'nullable|string|max:255',
            'delivery_address.address_line_3' => 'nullable|string|max:255',
            'delivery_address.postal_code' => 'required|string|max:20',
            'delivery_address.post_town' => 'required|string|max:255',
            'billing_address' => ['required', 'array'],
            'billing_address.recipient_name' => 'required|string|max:255',
            'billing_address.email' => 'required|email|max:255',
            'billing_address.phone' => 'required|string|max:20',
            'billing_address.address_line_1' => 'required|string|max:255',
            'billing_address.address_line_2' => 'nullable|string|max:255',
            'billing_address.address_line_3' => 'nullable|string|max:255',
            'billing_address.postal_code' => 'required|string|max:20',
            'billing_address.post_town' => 'required|string|max:255',
            'microsite' => ['required_if:campain_type,microsite', 'array'],
            'microsite.ask_size' => ['required_if:campain_type,microsite', Rule::in(['yes', 'no'])],
            'microsite.input_type' => ['required_if:campain_type,microsite', Rule::in(['plain_text', 'options'])],
            'microsite.options' => ['required_if:campain_type,microsite', 'array'],
            'gift_redemption' => ['required_if:campain_type,gift_redemption', 'array'],
            'gift_redemption.selected_items' => ['required_if:campain_type,gift_redemption', 'array'],
        ];
    }

    public function messages()
    {
        return [
            'user_id.exists' => 'The selected user does not exist.',
            'gift_box_id.exists' => 'The selected gift box does not exist.',
            'products.*.product_id.exists' => 'One or more selected products do not exist.',
            'campain_type.in' => 'Campaign type must be either microsite or gift_redemption.',
            'gift_box_type.in' => 'Gift box type must be gifte_branded, custom_branding, or plain.',
            'microsite.required_if' => 'Microsite details are required when campaign type is microsite.',
            'gift_redemption.required_if' => 'Gift redemption details are required when campaign type is gift_redemption.',
        ];
    }
}
