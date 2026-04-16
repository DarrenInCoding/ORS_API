<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'quantity' => (float) $this->quantity,
            'unit' => $this->unit,
            'running_balance' => (float) $this->running_balance,
            'notes' => $this->notes,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'category' => new RecyclableCategoryResource($this->whenLoaded('category')),
            'recycle_order' => new RecycleOrderResource($this->whenLoaded('recycleOrder')),
            'recorder' => new UserResource($this->whenLoaded('recorder')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
