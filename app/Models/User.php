<?php

namespace App\Models;

use App\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'is_super_admin',
        'is_owner',
        'role',
        'current_session_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'current_session_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_owner' => 'boolean',
        ];
    }

    // ── Filament Integration ─────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'superadmin') {
            return $this->is_super_admin === true;
        }

        return true;
    }

    /**
     * Returns projects this user can access in Filament panel.
     * Owner: all tenant projects. Others: only assigned projects.
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($panel->getId() === 'superadmin') {
            return collect();
        }

        if ($this->isOwner()) {
            return Project::where('tenant_id', $this->tenant_id)->get();
        }

        // Non-owner: only projects assigned via project_user pivot
        return $this->assignedProjects;
    }

    /**
     * Check if user can access a specific project (Filament tenant).
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // $tenant is a Project in Filament context
        if ($this->tenant_id !== $tenant->tenant_id) {
            return false;
        }

        if ($this->isOwner()) {
            return true;
        }

        return $this->assignedProjects()->where('projects.id', $tenant->id)->exists();
    }

    // ── Relationships ────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Projects assigned to this user via pivot table.
     * Owner doesn't need pivot entries — they access everything.
     */
    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * All projects of the user's tenant (convenience method).
     */
    public function allTenantProjects(): Collection
    {
        return Project::where('tenant_id', $this->tenant_id)->get();
    }

    /**
     * Legacy compatibility: returns projects collection.
     * @deprecated Use getTenants() or assignedProjects() instead
     */
    public function projects(): Collection
    {
        if ($this->isOwner()) {
            return $this->allTenantProjects();
        }
        return $this->assignedProjects()->get();
    }
}
