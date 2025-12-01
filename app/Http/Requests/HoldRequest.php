<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoldRequest extends FormRequest
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
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required.',
            'product_id.exists'   => 'Product not found.',
            'qty.required'        => 'Quantity is required.',
            'qty.integer'         => 'Quantity must be a number.',
            'qty.min'             => 'Quantity must be at least 1.',
        ];
    }
}
