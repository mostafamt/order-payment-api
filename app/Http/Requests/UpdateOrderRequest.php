<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'items' => 'sometimes|array|min:1',
            'items.*.product_name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'status' => 'sometimes|in:pending,confirmed,cancelled',
            'shipping_name' => 'sometimes|string|max:255',
            'shipping_phone' => 'sometimes|string|max:20',
            'shipping_address' => 'sometimes|string|max:500',
            'shipping_city' => 'sometimes|string|max:100',
            'shipping_province' => 'sometimes|string|max:100',
            'shipping_postal_code' => 'sometimes|string|max:20',
            'shipping_country' => 'sometimes|string|max:100',
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
            'items.array' => 'Items must be an array',
            'items.min' => 'At least one item is required',
            'items.*.product_name.required_with' => 'Product name is required for each item',
            'items.*.product_name.string' => 'Product name must be a string',
            'items.*.product_name.max' => 'Product name cannot exceed 255 characters',
            'items.*.quantity.required_with' => 'Quantity is required for each item',
            'items.*.quantity.integer' => 'Quantity must be a valid integer',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'items.*.price.required_with' => 'Price is required for each item',
            'items.*.price.numeric' => 'Price must be a valid number',
            'items.*.price.min' => 'Price cannot be negative',
            'status.in' => 'Status must be one of: pending, confirmed, or cancelled',
            'shipping_name.string' => 'Shipping name must be a string',
            'shipping_name.max' => 'Shipping name cannot exceed 255 characters',
            'shipping_phone.string' => 'Shipping phone must be a string',
            'shipping_phone.max' => 'Shipping phone cannot exceed 20 characters',
            'shipping_address.string' => 'Shipping address must be a string',
            'shipping_address.max' => 'Shipping address cannot exceed 500 characters',
            'shipping_city.string' => 'Shipping city must be a string',
            'shipping_city.max' => 'Shipping city cannot exceed 100 characters',
            'shipping_province.string' => 'Shipping province must be a string',
            'shipping_province.max' => 'Shipping province cannot exceed 100 characters',
            'shipping_postal_code.string' => 'Shipping postal code must be a string',
            'shipping_postal_code.max' => 'Shipping postal code cannot exceed 20 characters',
            'shipping_country.string' => 'Shipping country must be a string',
            'shipping_country.max' => 'Shipping country cannot exceed 100 characters',
        ];
    }
}
