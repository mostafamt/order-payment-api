<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
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
            'items.required' => 'At least one item is required to create an order',
            'items.array' => 'Items must be an array',
            'items.min' => 'At least one item is required',
            'items.*.product_name.required' => 'Product name is required for each item',
            'items.*.product_name.string' => 'Product name must be a string',
            'items.*.product_name.max' => 'Product name cannot exceed 255 characters',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.integer' => 'Quantity must be a valid integer',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'items.*.price.required' => 'Price is required for each item',
            'items.*.price.numeric' => 'Price must be a valid number',
            'items.*.price.min' => 'Price cannot be negative',
            'notes.string' => 'Notes must be a string',
            'notes.max' => 'Notes cannot exceed 1000 characters',
        ];
    }
}
