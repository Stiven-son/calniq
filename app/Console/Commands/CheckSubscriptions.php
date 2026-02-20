<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiredMail;
use App\Mail\TrialEndingSoonMail;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckSubscriptions extends Command
{
    protected $signature = 'subscriptions:check';

    protected $description = 'Check trial and subscription expirations, send notifications';

    public function handle(): int
    {
        $this->info('Checking subscriptions...');

        $this->checkExpiringTrials();
        $this->checkExpiredTrials();
        $this->checkExpiringSubscriptions();
        $this->checkExpiredSubscriptions();

        $this->info('Done.');
        return 0;
    }

    private function checkExpiringTrials(): void
    {
        $tenants = Tenant::where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->get();

        foreach ($tenants as $tenant) {
            $daysLeft = $tenant->trialDaysRemaining();

            if ($daysLeft <= $tenant->notification_days_before && $this->shouldNotify($tenant)) {
                Mail::to($tenant->email)->send(new TrialEndingSoonMail($tenant));

                $tenant->update(['last_notified_at' => now()]);

                $this->line("  → Trial ending notification sent to {$tenant->email} ({$daysLeft} days left)");
            }
        }
    }

    private function checkExpiredTrials(): void
    {
        $tenants = Tenant::where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->expireSubscription();

            Mail::to($tenant->email)->send(new SubscriptionExpiredMail($tenant));

            $this->line("  → Trial expired for {$tenant->email}");
        }
    }

    private function checkExpiringSubscriptions(): void
    {
        $tenants = Tenant::where('subscription_status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '>', now())
            ->get();

        foreach ($tenants as $tenant) {
            $daysLeft = $tenant->subscriptionDaysRemaining();

            if ($daysLeft <= $tenant->notification_days_before && $this->shouldNotify($tenant)) {
                Mail::to($tenant->email)->send(new TrialEndingSoonMail($tenant));

                $tenant->update(['last_notified_at' => now()]);

                $this->line("  → Subscription ending notification sent to {$tenant->email} ({$daysLeft} days left)");
            }
        }
    }

    private function checkExpiredSubscriptions(): void
    {
        $tenants = Tenant::where('subscription_status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<=', now())
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->expireSubscription();

            Mail::to($tenant->email)->send(new SubscriptionExpiredMail($tenant));

            $this->line("  → Subscription expired for {$tenant->email}");
        }
    }

    private function shouldNotify(Tenant $tenant): bool
    {
        if (!$tenant->last_notified_at) {
            return true;
        }

        // Don't notify more than once per day
        return $tenant->last_notified_at->lt(now()->startOfDay());
    }
}