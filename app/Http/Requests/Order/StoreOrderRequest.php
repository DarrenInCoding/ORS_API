<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'type' => ['nullable', 'string', 'in:drop_off,pickup'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'pickup_address' => ['required_if:type,pickup', 'nullable', 'string', 'max:500'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:recyclable_categories,id'],
            'items.*.estimated_weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one recyclable item is required.',
            'items.min' => 'At least one recyclable item is required.',
            'pickup_address.required_if' => 'Pickup address is required for pickup orders.',
        ];
    }
}
