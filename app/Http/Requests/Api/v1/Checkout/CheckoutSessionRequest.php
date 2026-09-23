<?php

namespace App\Http\Requests\Api\v1\Checkout;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_email' => ['required', 'email'],
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'shipping_address' => ['sometimes', 'array'],
            'shipping_address.line1' => ['required_with:shipping_address', 'string'],
            'shipping_address.city' => ['required_with:shipping_address', 'string'],
            'shipping_address.state' => ['sometimes', 'string'],
            'shipping_address.postal_code' => ['required_with:shipping_address', 'string'],
            'shipping_address.country' => ['required_with:shipping_address', 'string'],
            'cart_token' => ['sometimes', 'string'],
            'success_url' => ['sometimes', 'url'],
            'cancel_url' => ['sometimes', 'url'],
        ];
    }
}
