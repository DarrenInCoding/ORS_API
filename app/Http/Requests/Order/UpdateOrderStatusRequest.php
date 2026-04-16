<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin()
            || $this->user()->isBranchManager()
            || $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(OrderStatus::values())],
            'staff_notes' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'exists:recycle_order_items,id'],
            'items.*.actual_weight' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'A rejection reason is required when rejecting an order.',
        ];
    }
}
