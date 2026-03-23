<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the audit logs for the user.
     */
    public function avatarUrl(): ?string
    {
        if ($this->avatar_path) {
            return \Illuminate\Support\Facades\Storage::url($this->avatar_path);
        }
        return null;
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the groups the user belongs to.
     */
    public function gruppen()
    {
        return $this->belongsToMany(Gruppe::class, 'gruppe_user');
    }

    /**
     * Get the positions (Stellen) held by this user.
     */
    public function stellen()
    {
        return $this->hasMany(Stelle::class);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter users by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->whereHas('roles', fn($q) => $q->where('name', $role));
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Superadministrator');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    /**
     * Check if the user has permission for a specific module.
     */
    public function hasModulePermission(string $module, string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        try {
            return $this->hasPermissionTo("{$module}.{$permission}");
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            return false;
        }
    }

    /**
     * Get the permission scopes for the user.
     */
    public function permissionScopes()
    {
        return $this->hasMany(PermissionScope::class);
    }

    /**
     * Check if the user has permission to access a specific scope.
     * 
     * @param string $permission The permission name (e.g., "hh.view")
     * @param string $scopeType The type of scope (e.g., "cost_center")
     * @param int $scopeId The ID of the specific scope entity
     * @return bool
     */
    public function hasPermissionToScope(string $permission, string $scopeType, int $scopeId): bool
    {
        // Superadmin has access to everything
        if ($this->hasRole('Superadministrator')) {
            return true;
        }

        // Check if user has the permission at all
        if (!$this->hasPermissionTo($permission)) {
            return false;
        }

        // Get the permission model
        try {
            $permissionModel = \Spatie\Permission\Models\Permission::findByName($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            return false;
        }

        // Check if user has permission without scope (grants access to all)
        if ($this->hasPermissionWithoutScope($permission)) {
            return true;
        }

        // Check if user has specific scope access
        return $this->permissionScopes()
            ->where('permission_id', $permissionModel->id)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->exists();
    }

    /**
     * Check if the user has a permission without any scope restrictions.
     * A permission without scope grants access to all entities.
     * 
     * @param string $permission The permission name (e.g., "hh.view")
     * @return bool
     */
    public function hasPermissionWithoutScope(string $permission): bool
    {
        // Superadmin has access to everything
        if ($this->hasRole('Superadministrator')) {
            return true;
        }

        // Check if user has the permission at all
        if (!$this->hasPermissionTo($permission)) {
            return false;
        }

        // Get the permission model
        try {
            $permissionModel = \Spatie\Permission\Models\Permission::findByName($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            return false;
        }

        // If no permission scopes exist for this user and permission, they have unrestricted access
        return !$this->permissionScopes()
            ->where('permission_id', $permissionModel->id)
            ->exists();
    }
}
