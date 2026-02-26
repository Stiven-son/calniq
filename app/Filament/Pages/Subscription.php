<?php

namespace App\Filament\Pages;

use App\Models\Plan;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Subscription extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Subscription';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.subscription';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('subscription');
    }

    public function getTenant(): Tenant
    {
        return Filament::getTenant()->tenant;
    }

    public function changePlan(int $planId): void
    {
        $plan = Plan::where('id', $planId)->where('is_active', true)->first();
        if (!$plan) return;

        $tenant = $this->getTenant();

        // Check downgrade limits
        $maxProjects = $plan->getMaxProjects();
        if ($maxProjects !== null && $tenant->projects()->count() > $maxProjects) {
            Notification::make()
                ->danger()
                ->title('Cannot downgrade')
                ->body("You have {$tenant->projects()->count()} projects. The {$plan->name} plan allows only {$maxProjects}. Please delete extra projects first.")
                ->send();
            return;
        }

        $oldName = $tenant->getPlanName();
        $tenant->changePlan($planId);

        Notification::make()
            ->success()
            ->title('Plan changed')
            ->body("Your plan has been changed from {$oldName} to {$plan->name}.")
            ->send();
    }
}