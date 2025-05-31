<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ServiceDetailsRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
            'description' => 'required|string',
            'service_id' => 'required|exists:services,id',
            'images' => 'required|array|min:1',
            'images.*' => 'file|mimes:jpg,png,jpeg,gif|max:20048',
            'case_studies.*.description' => 'required|string',
            'what_includes.*.item' => 'required|string',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',
        ];
    }
}
