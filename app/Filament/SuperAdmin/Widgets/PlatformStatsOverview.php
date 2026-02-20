<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Booking;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRevenue = Booking::whereIn('status', ['confirmed', 'completed'])
            ->sum('total');

        $monthlyRevenue = Booking::whereIn('status', ['confirmed', 'completed'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');

        $todayBookings = Booking::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Tenants', Tenant::count())
                ->description('Active accounts')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Total Projects', Project::count())
                ->description(Project::where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-folder')
                ->color('info'),

            Stat::make('Total Bookings', Booking::count())
                ->description($todayBookings . ' today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('$' . number_format($monthlyRevenue, 2) . ' this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),

            Stat::make('Users', User::count())
                ->description(User::where('is_super_admin', true)->count() . ' super admins')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Pending Review', Booking::where('status', 'pending')->count())
                ->description('Across all tenants')
                ->descriptionIcon('heroicon-m-clock')
                ->color(Booking::where('status', 'pending')->count() > 0 ? 'danger' : 'gray'),
        ];
    }
}