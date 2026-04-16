<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecycleOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'type' => $this->type,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'total_weight' => (float) $this->total_weight,
            'total_amount' => (float) $this->total_amount,
            'customer_notes' => $this->customer_notes,
            'staff_notes' => $this->staff_notes,
            'rejection_reason' => $this->rejection_reason,
            'pickup_address' => $this->pickup_address,
            'pickup_latitude' => $this->pickup_latitude,
            'pickup_longitude' => $this->pickup_longitude,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'handler' => new UserResource($this->whenLoaded('handler')),
            'items' => RecycleOrderItemResource::collection($this->whenLoaded('items')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
