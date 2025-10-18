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
        ];
    }
}
