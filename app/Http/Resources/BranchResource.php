<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'email' => $this->email,
            'operating_hours' => $this->operating_hours,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'manager' => new UserResource($this->whenLoaded('manager')),
            'staff' => UserResource::collection($this->whenLoaded('staff')),
            'staff_count' => $this->whenCounted('staff'),
            'orders_count' => $this->whenCounted('recycleOrders'),
            'distance' => $this->when(isset($this->distance), fn() => round($this->distance, 2)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
