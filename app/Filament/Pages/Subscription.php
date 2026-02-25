<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
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

    public function changePlan(string $plan): void
    {
        if (!in_array($plan, ['starter', 'pro', 'agency'])) {
            return;
        }

        $tenant = $this->getTenant();
        $currentPlan = $tenant->plan;

        // Check downgrade limits
        $limits = Tenant::PLAN_LIMITS[$plan];
        if ($limits['max_projects'] !== null && $tenant->projects()->count() > $limits['max_projects']) {
            Notification::make()
                ->danger()
                ->title('Cannot downgrade')
                ->body("You have {$tenant->projects()->count()} projects. The {$plan} plan allows only {$limits['max_projects']}. Please delete extra projects first.")
                ->send();
            return;
        }

        $tenant->changePlan($plan);

        Notification::make()
            ->success()
            ->title('Plan changed')
            ->body("Your plan has been changed from " . ucfirst($currentPlan) . " to " . ucfirst($plan) . ".")
            ->send();
    }
}