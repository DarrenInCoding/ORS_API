<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecyclableCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'unit' => $this->unit,
            'price_per_unit' => (float) $this->price_per_unit,
            'min_quantity' => (float) $this->min_quantity,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'parent_id' => $this->parent_id,
            'parent' => new RecyclableCategoryResource($this->whenLoaded('parent')),
            'children' => RecyclableCategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
