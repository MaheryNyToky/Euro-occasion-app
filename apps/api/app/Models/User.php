<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'status',
        'mfa_enabled',
        'mfa_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'mfa_enabled' => 'boolean',
        ];
    }

    /**
     * Registered devices for this user.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Roles assigned to this user.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Assign a role to this user, optionally restricted to a specific scope.
     */
    public function assignRole(Role|string $role, ?string $scopeType = null, ?string $scopeId = null): UserRole
    {
        $roleId = $role instanceof Role ? $role->id : Role::where('code', $role)->value('id');

        return $this->userRoles()->create([
            'tenant_id' => $this->tenant_id,
            'role_id' => $roleId,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
        ]);
    }

    /**
     * Check if user has a given role.
     */
    public function hasRole(string $roleCode, ?string $scopeType = null, ?string $scopeId = null): bool
    {
        return $this->userRoles()
            ->whereHas('role', fn ($query) => $query->where('code', $roleCode))
            ->where(function ($query) use ($scopeType, $scopeId) {
                $query->whereNull('scope_type'); // Global role
                if ($scopeType !== null && $scopeId !== null) {
                    $query->orWhere(function ($q) use ($scopeType, $scopeId) {
                        $q->where('scope_type', $scopeType)
                          ->where('scope_id', $scopeId);
                    });
                }
            })
            ->exists();
    }

    /**
     * Check if user has permission to perform an action, taking into account data scope.
     */
    public function hasPermissionTo(string $permissionCode, ?string $scopeType = null, ?string $scopeId = null): bool
    {
        return $this->userRoles()
            ->whereHas('role.permissions', fn ($query) => $query->where('code', $permissionCode))
            ->where(function ($query) use ($scopeType, $scopeId) {
                // If the user has global permission for the tenant
                $query->whereNull('scope_type');
                
                // Or if they have scoped permission matching the requested resource
                if ($scopeType !== null && $scopeId !== null) {
                    $query->orWhere(function ($q) use ($scopeType, $scopeId) {
                        $q->where('scope_type', $scopeType)
                          ->where('scope_id', $scopeId);
                    });
                }
            })
            ->exists();
    }

    /**
     * Retrieve all effective permissions for this user.
     */
    public function getAllPermissions(): array
    {
        $userRoles = $this->userRoles()->with('role.permissions')->get();

        $permissions = [];
        foreach ($userRoles as $userRole) {
            foreach ($userRole->role->permissions as $perm) {
                $permissions[] = [
                    'code' => $perm->code,
                    'name' => $perm->name,
                    'category' => $perm->category,
                    'scope_type' => $userRole->scope_type,
                    'scope_id' => $userRole->scope_id,
                ];
            }
        }

        return $permissions;
    }
}
