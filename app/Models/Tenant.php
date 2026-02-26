<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'plan',           // legacy varchar — will be removed later
        'plan_id',        // FK to plans table
        'subscription_status',
        'stripe_customer_id',
        'subscription_ends_at',
        'trial_ends_at',
        'notification_days_before',
        'last_notified_at',
        'referred_by',
        'is_partner',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'last_notified_at' => 'datetime',
        'is_partner' => 'boolean',
    ];

    // ── Plan Limits (via Plan model) ─────────────────────────

    /**
     * Get plan limit by key.
     * Uses Plan model → limits JSON.
     */
    public function getPlanLimit(string $key, $default = null)
    {
        return $this->currentPlan?->getLimit($key, $default) ?? $default;
    }

    public function getMaxProjects(): ?int
    {
        return $this->getPlanLimit('max_projects');
    }

    public function getMaxBookingsPerMonth(): ?int
    {
        return $this->getPlanLimit('max_bookings_per_month');
    }

    public function getMaxAdminsPerProject(): ?int
    {
        return $this->getPlanLimit('max_admins_per_project', 1);
    }

    public function getMaxManagersPerProject(): ?int
    {
        return $this->getPlanLimit('max_managers_per_project', 1);
    }

    public function getMaxWorkersPerProject(): ?int
    {
        return $this->getPlanLimit('max_workers_per_project', 1);
    }

    public function getPlanPrice(): float
    {
        return (float) ($this->currentPlan?->price ?? 0);
    }

    public function getPlanName(): string
    {
        return $this->currentPlan?->name ?? 'No Plan';
    }

    public function allowsAddons(): bool
    {
        return $this->currentPlan?->allows_addons ?? false;
    }

    public function canCreateProject(): bool
    {
        $max = $this->getMaxProjects();
        if ($max === null) return true;
        return $this->projects()->count() < $max;
    }

    public function canCreateBooking(): bool
    {
        $max = $this->getMaxBookingsPerMonth();
        if ($max === null) return true;

        return $this->getMonthlyBookingCount() < $max;
    }

    public function getMonthlyBookingCount(): int
    {
        return Booking::whereHas('project', fn ($q) => $q->where('tenant_id', $this->id))
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    /**
     * Check if tenant can add a user with given role to a project.
     */
    public function canAddUserWithRole(string $role, $projectId): bool
    {
        $maxKey = "max_{$role}s_per_project";
        $max = $this->getPlanLimit($maxKey);
        if ($max === null) return true;

        $count = \Illuminate\Support\Facades\DB::table('project_user')
            ->where('project_id', $projectId)
            ->where('role', $role)
            ->count();

        return $count < $max;
    }

    // ── Backward Compatibility (remove after full migration) ─

    /**
     * @deprecated Use currentPlan relationship instead
     */
    public function getPlanLimits(): array
    {
        $plan = $this->currentPlan;
        if (!$plan) {
            return [
                'max_projects' => 1,
                'max_bookings_per_month' => 100,
                'price' => 0,
            ];
        }

        return array_merge($plan->limits, ['price' => (int) $plan->price]);
    }

    // ── Subscription Status ──────────────────────────────────

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        // Active subscription (no end date = indefinite, e.g. staging/demo)
        if ($this->subscription_status === 'active') {
            if (!$this->subscription_ends_at) return true;
            return $this->subscription_ends_at->isFuture();
        }

        // On trial
        if ($this->isOnTrial()) {
            return true;
        }

        return false;
    }

    public function hasExpired(): bool
    {
        return !$this->isActive();
    }

    public function trialDaysRemaining(): int
    {
        if (!$this->trial_ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    public function subscriptionDaysRemaining(): int
    {
        if (!$this->subscription_ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->subscription_ends_at, false));
    }

    public function daysRemaining(): int
    {
        if ($this->isOnTrial()) {
            return $this->trialDaysRemaining();
        }
        return $this->subscriptionDaysRemaining();
    }

    public function activateSubscription(int $planId, int $months = 1): void
    {
        $plan = Plan::find($planId);
        $this->update([
            'plan_id' => $planId,
            'plan' => $plan ? explode('-', $plan->slug)[0] : $this->plan, // keep legacy in sync
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addMonths($months),
            'last_notified_at' => null,
        ]);
    }

    public function cancelSubscription(): void
    {
        $this->update([
            'subscription_status' => 'cancelled',
        ]);
    }

    public function expireSubscription(): void
    {
        $this->update([
            'subscription_status' => 'expired',
        ]);
    }

    public function changePlan(int $planId): void
    {
        $plan = Plan::find($planId);
        $this->update([
            'plan_id' => $planId,
            'plan' => $plan ? explode('-', $plan->slug)[0] : $this->plan,
        ]);
    }

    public function getStatusBadge(): string
    {
        return match ($this->subscription_status) {
            'trial' => $this->isOnTrial() ? 'Trial' : 'Trial Expired',
            'active' => 'Active',
            'past_due' => 'Past Due',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            default => 'Unknown',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->subscription_status) {
            'trial' => $this->isOnTrial() ? 'info' : 'danger',
            'active' => 'success',
            'past_due' => 'warning',
            'cancelled' => 'danger',
            'expired' => 'danger',
            default => 'gray',
        };
    }

    // ── Relationships ────────────────────────────────────────

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Tenant::class, 'referred_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->users()->where('is_owner', true)->first();
    }
}
