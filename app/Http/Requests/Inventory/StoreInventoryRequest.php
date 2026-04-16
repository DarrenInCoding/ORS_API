<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
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
            'branch_id' => ['required', 'exists:branches,id'],
            'category_id' => ['required', 'exists:recyclable_categories,id'],
            'type' => ['required', 'string', Rule::in(InventoryType::values())],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', 'in:kg,unit,piece'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
