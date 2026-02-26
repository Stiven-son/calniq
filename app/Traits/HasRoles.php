<?php

namespace App\Traits;

use App\Models\Project;
use Filament\Facades\Filament;

trait HasRoles
{
    // Role constants for project_user pivot
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_WORKER = 'worker';

    // Roles available for assignment (Owner is not assignable — it's is_owner flag)
    const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_WORKER => 'Worker',
    ];

    /**
     * Access matrix: resource → which project roles can access.
     * Owner and SuperAdmin bypass this matrix entirely.
     */
    const ROLE_ACCESS = [
        'dashboard'             => ['admin', 'manager', 'worker'],
        'bookings'              => ['admin', 'manager', 'worker'],
        'bookings.edit_status'  => ['admin', 'manager', 'worker'],
        'bookings.edit_details' => ['admin', 'manager'],
        'bookings.delete'       => ['admin'],
        'service_setup'         => ['admin'],
        'categories'            => ['admin'],
        'services'              => ['admin'],
        'promo_codes'           => ['admin', 'manager'],
        'embed_code'            => ['admin'],
        'subscription'          => [],       // Owner only
        'settings'              => ['admin', 'manager'],
        'locations'             => ['admin', 'manager'],
        'time_slots'            => ['admin', 'manager'],
        'blocked_dates'         => ['admin', 'manager'],
        'webhooks'              => ['admin'],
        'users'                 => ['admin'],  // Owner + Admin (Admin adds Manager/Worker to own project)
    ];

    // ── Role Checks ──────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->is_owner === true;
    }

    /**
     * Get this user's role for a specific project.
     * Owner returns 'owner'. Others check project_user pivot.
     */
    public function getRoleForProject(Project $project): ?string
    {
        if ($this->isOwner()) {
            return 'owner';
        }

        return $this->assignedProjects()
            ->where('projects.id', $project->id)
            ->first()
            ?->pivot
            ?->role;
    }

    /**
     * Check if user is Admin for current Filament project context.
     */
    public function isAdmin(): bool
    {
        if ($this->isOwner()) return true;

        $project = Filament::getTenant();
        if (!$project) return false;

        return $this->getRoleForProject($project) === self::ROLE_ADMIN;
    }

    /**
     * Check if user is Manager for current Filament project context.
     */
    public function isManager(): bool
    {
        $project = Filament::getTenant();
        if (!$project) return false;

        return $this->getRoleForProject($project) === self::ROLE_MANAGER;
    }

    /**
     * Check if user is Worker for current Filament project context.
     */
    public function isWorker(): bool
    {
        $project = Filament::getTenant();
        if (!$project) return false;

        return $this->getRoleForProject($project) === self::ROLE_WORKER;
    }

    /**
     * Check access to a specific resource.
     * SuperAdmin and Owner bypass the matrix.
     * Others checked against ROLE_ACCESS for current project.
     */
    public function hasAccessTo(string $resource): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        if ($this->isOwner()) {
            return true;
        }

        // Get current project from Filament context
        $project = Filament::getTenant();
        if (!$project) {
            return false;
        }

        $role = $this->getRoleForProject($project);
        if (!$role) {
            return false;
        }

        $allowedRoles = self::ROLE_ACCESS[$resource] ?? [];
        return in_array($role, $allowedRoles);
    }

    /**
     * Get display label for user's role in current context.
     */
    public function getRoleLabelAttribute(): string
    {
        if ($this->isOwner()) {
            return 'Owner';
        }

        $project = Filament::getTenant();
        if ($project) {
            $role = $this->getRoleForProject($project);
            return self::ROLES[$role] ?? 'No Role';
        }

        return self::ROLES[$this->role] ?? 'Unknown';
    }

    /**
     * Can this user manage team members?
     * Owner: can manage all. Admin: can add Manager/Worker to their project.
     */
    public function canManageTeam(): bool
    {
        return $this->isOwner();
    }

    /**
     * Can this user add users to a specific project?
     * Owner: yes (any role). Admin: only Manager/Worker.
     */
    public function canAddUserToProject(Project $project, string $role): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        // Admin can add Manager and Worker to their own project
        if ($this->getRoleForProject($project) === self::ROLE_ADMIN) {
            return in_array($role, [self::ROLE_MANAGER, self::ROLE_WORKER]);
        }

        return false;
    }
}
