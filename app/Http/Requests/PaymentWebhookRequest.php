<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentWebhookRequest extends FormRequest
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
            'data.order_id' => ['required', 'integer', 'exists:orders,id'],
            'data.status' => ['required', 'string', 'in:success,failure,pending'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.order_id.required' => 'Order ID is required.',
            'data.order_id.integer'  => 'Order ID must be an integer.',
            'data.order_id.exists'   => 'Order not found.',
            'data.status.required'   => 'Status is required.',
            'data.status.string'     => 'Status must be a string.',
            'data.status.in'         => 'Status must be one of: success, failure, pending.',
        ];
    }
}
