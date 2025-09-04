<?php

namespace App\Http\Requests\V1;

use App\Models\Product;
use App\Models\ProductPriceRange;
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
            'campaign_type' => ['nullable', 'in:microsite,gift_redemption'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'gift_redeem_quantity' => ['nullable', 'integer', 'min:0'],
            'multiple_delivery_address' => ['boolean'],
            'user_currency' => ['nullable', 'string', 'max:3', 'exists:conversion_rates,currency'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $productId = $this->input("products.{$index}.product_id");
                    $numberOfBoxes = $this->input('number_of_boxes', 1); // Default to 1 if not set
                    $quantity = $value ?? $numberOfBoxes; // Use number_of_boxes as default if quantity is null
                    $product = Product::find($productId);
                    if (!$product) {
                        $fail("Product ID {$productId} not found");
                    } elseif ($quantity > $product->quantity) {
                        $fail("Quantity {$quantity} exceeds available stock {$product->quantity} for product ID {$productId}");
                    }
                    $minQuantity = ProductPriceRange::where('product_id', $productId)->min('min_quantity');
                    if ($minQuantity !== null && $quantity < $minQuantity) {
                        $fail("Quantity {$quantity} is less than the minimum required quantity {$minQuantity} for product ID {$productId}");
                    }
                },
            ],
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
            'pay_now' => ['boolean'],
            'get_invoice' => ['boolean'],
        ];
    }

    public function messages()
    {
        return [
            'user_currency.exists' => 'The selected user currency is not supported.',
            'gift_box_id.exists' => 'The selected gift box does not exist.',
            'products.*.product_id.exists' => 'One or more product IDs are invalid.',
            'products.*.quantity.required' => 'Quantity is required for each product.',
            'delivery_address.required_if' => 'Delivery address is required when multiple delivery addresses are not enabled.',
            'pay_now.boolean' => 'The pay now field must be a boolean.',
            'get_invoice.boolean' => 'The get invoice field must be a boolean.',
        ];
    }
}
