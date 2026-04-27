<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'social_accounts' => $this->whenLoaded('socialAccounts', function () {
                return $this->socialAccounts->map(fn($account) => [
                    'provider' => $account->provider,
                    'connected_at' => $account->created_at->toISOString(),
                ]);
            }),
            'managed_branch' => new \App\Http\Resources\BranchResource($this->whenLoaded('managedBranch')),
            'assigned_branches' => \App\Http\Resources\BranchResource::collection($this->whenLoaded('assignedBranches')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
