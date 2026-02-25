<?php

namespace App\Traits;

trait HasRoles
{
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_WORKER = 'worker';

    const ROLES = [
        self::ROLE_ADMIN => 'Administrator',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_WORKER => 'Worker',
    ];

    const ROLE_ACCESS = [
        'dashboard'           => ['admin', 'manager', 'worker'],
        'bookings'            => ['admin', 'manager', 'worker'],
        'bookings.edit_status'=> ['admin', 'manager', 'worker'],
        'bookings.edit_details' => ['admin', 'manager'],
        'bookings.delete'     => ['admin'],
        'service_setup'       => ['admin'],
        'categories'          => ['admin'],
        'services'            => ['admin'],
        'promo_codes'         => ['admin', 'manager'],
        'embed_code'          => ['admin'],
        'subscription'        => ['admin'],
        'settings'            => ['admin', 'manager'],
        'locations'           => ['admin', 'manager'],
        'time_slots'          => ['admin', 'manager'],
        'blocked_dates'       => ['admin', 'manager'],
        'webhooks'            => ['admin'],
        'users'               => ['admin'],
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isWorker(): bool
    {
        return $this->role === self::ROLE_WORKER;
    }

    public function hasAccessTo(string $resource): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $allowedRoles = self::ROLE_ACCESS[$resource] ?? [];
        return in_array($this->role, $allowedRoles);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? 'Unknown';
    }
}
