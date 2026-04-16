<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method?->value,
            'payment_method_label' => $this->payment_method?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toISOString(),
            'order' => new RecycleOrderResource($this->whenLoaded('order')),
            'user' => new UserResource($this->whenLoaded('user')),
            'processor' => new UserResource($this->whenLoaded('processor')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
