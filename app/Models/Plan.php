<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'limits',
        'allows_addons',
        'is_active',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'limits' => 'array',
        'allows_addons' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    // ── Limit Accessors ──────────────────────────────────────

    public function getLimit(string $key, $default = null)
    {
        return $this->limits[$key] ?? $default;
    }

    public function getMaxProjects(): ?int
    {
        return $this->getLimit('max_projects');
    }

    public function getMaxBookingsPerMonth(): ?int
    {
        return $this->getLimit('max_bookings_per_month');
    }

    public function getMaxAdminsPerProject(): ?int
    {
        return $this->getLimit('max_admins_per_project');
    }

    public function getMaxManagersPerProject(): ?int
    {
        return $this->getLimit('max_managers_per_project');
    }

    public function getMaxWorkersPerProject(): ?int
    {
        return $this->getLimit('max_workers_per_project');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->public();
    }

    // ── Relationships ────────────────────────────────────────

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isPartnerPlan(): bool
    {
        return str_starts_with($this->slug, 'partner');
    }

    public function isStarterPlan(): bool
    {
        return str_starts_with($this->slug, 'starter');
    }

    public function isProPlan(): bool
    {
        return str_starts_with($this->slug, 'pro');
    }
}
