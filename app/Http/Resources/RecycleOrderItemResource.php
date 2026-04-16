<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecycleOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => new RecyclableCategoryResource($this->whenLoaded('category')),
            'estimated_weight' => $this->estimated_weight ? (float) $this->estimated_weight : null,
            'actual_weight' => $this->actual_weight ? (float) $this->actual_weight : null,
            'quantity' => $this->quantity,
            'price_per_unit' => (float) $this->price_per_unit,
            'subtotal' => (float) $this->subtotal,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
