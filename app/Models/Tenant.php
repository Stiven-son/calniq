<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'plan',
        'subscription_status',
        'stripe_customer_id',
        'subscription_ends_at',
        'trial_ends_at',
        'notification_days_before',
        'last_notified_at',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'last_notified_at' => 'datetime',
    ];

    // ── Plan Limits ──────────────────────────────────────────

    const PLAN_LIMITS = [
        'starter' => [
            'max_projects' => 1,
            'max_bookings_per_month' => 100,
            'white_label' => false,
            'promo_codes' => false,
            'price' => 29,
        ],
        'pro' => [
            'max_projects' => 5,
            'max_bookings_per_month' => null, // unlimited
            'white_label' => true,
            'promo_codes' => true,
            'price' => 59,
        ],
        'agency' => [
    'max_projects' => 20,
    'max_bookings_per_month' => null,
    'white_label' => true,
    'promo_codes' => true,
    'price' => 149,
],
    ];

    public function getPlanLimits(): array
    {
        return self::PLAN_LIMITS[$this->plan] ?? self::PLAN_LIMITS['starter'];
    }

    public function getPlanPrice(): int
    {
        return $this->getPlanLimits()['price'];
    }

    public function getMaxProjects(): ?int
    {
        return $this->getPlanLimits()['max_projects'];
    }

    public function getMaxBookingsPerMonth(): ?int
    {
        return $this->getPlanLimits()['max_bookings_per_month'];
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

        $count = \App\Models\Booking::whereHas('project', fn ($q) => $q->where('tenant_id', $this->id))
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return $count < $max;
    }

    public function getMonthlyBookingCount(): int
    {
        return \App\Models\Booking::whereHas('project', fn ($q) => $q->where('tenant_id', $this->id))
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
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
        // Active subscription
        if ($this->subscription_status === 'active' && $this->subscription_ends_at && $this->subscription_ends_at->isFuture()) {
            return true;
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

    public function activateSubscription(string $plan, int $months = 1): void
    {
        $this->update([
            'plan' => $plan,
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

    public function changePlan(string $newPlan): void
    {
        $this->update(['plan' => $newPlan]);
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

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}