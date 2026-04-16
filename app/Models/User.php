<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'avatar',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function managedBranch(): HasOne
    {
        return $this->hasOne(Branch::class, 'manager_id');
    }

    public function assignedBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_staff')
            ->withPivot('position', 'assigned_at')
            ->withTimestamps();
    }

    public function recycleOrders(): HasMany
    {
        return $this->hasMany(RecycleOrder::class, 'customer_id');
    }

    public function handledOrders(): HasMany
    {
        return $this->hasMany(RecycleOrder::class, 'handled_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // ── Helpers ────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isBranchManager(): bool
    {
        return $this->role === UserRole::BRANCH_MANAGER;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::STAFF;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles);
    }
}
