<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
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
            'order_id' => 'required|exists:orders,id',
            'method' => 'required|in:credit_card,paypal,stripe',
            'payment_data' => 'required|array',
            // Additional validation rules based on payment method
            'payment_data.card_number' => 'required_if:method,credit_card|string',
            'payment_data.cvv' => 'required_if:method,credit_card|string',
            'payment_data.expiry_date' => 'sometimes|string',
            'payment_data.paypal_email' => 'required_if:method,paypal|email',
            'payment_data.stripe_token' => 'required_if:method,stripe|string',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'Order does not exist',
            'method.required' => 'Payment method is required',
            'method.in' => 'Invalid payment method. Accepted methods: credit_card, paypal, stripe',
            'payment_data.required' => 'Payment data is required',
            'payment_data.card_number.required_if' => 'Card number is required for credit card payments',
            'payment_data.cvv.required_if' => 'CVV is required for credit card payments',
            'payment_data.paypal_email.required_if' => 'PayPal email is required for PayPal payments',
            'payment_data.stripe_token.required_if' => 'Stripe token is required for Stripe payments',
        ];
    }
}
