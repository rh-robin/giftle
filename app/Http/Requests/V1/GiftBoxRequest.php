<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class GiftBoxRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'gifte_branded_price' => 'required|integer|min:0',
            'custom_branding_price' => 'required|integer|min:0',
            'plain_price' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The gift box name is required',
            'gifte_branded_price.required' => 'The branded price is required',
            'custom_branding_price.required' => 'The custom branding price is required',
            'plain_price.required' => 'The plain price is required',
            'image.required' => 'The image is required',
            'image.image' => 'The image must be an image',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image may not be greater than 20MB.',
            'status.required' => 'The status is required',
            '*.integer' => 'Prices must be whole numbers',
            '*.min' => 'Prices cannot be negative',
        ];
    }
}
