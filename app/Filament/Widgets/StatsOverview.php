<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $projectId = Filament::getTenant()->id;

        $todayBookings = Booking::where('project_id', $projectId)
            ->whereDate('scheduled_date', now()->toDateString())
            ->count();

        $weekBookings = Booking::where('project_id', $projectId)
            ->whereBetween('scheduled_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $monthRevenue = Booking::where('project_id', $projectId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->sum('total');

        $lastMonthRevenue = Booking::where('project_id', $projectId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->sum('total');

        $pendingBookings = Booking::where('project_id', $projectId)
            ->where('status', 'pending')
            ->count();

        $revenueChange = $lastMonthRevenue > 0
            ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : ($monthRevenue > 0 ? 100 : 0);

        return [
            Stat::make('Today\'s Bookings', $todayBookings)
                ->description('Scheduled for today')
                ->icon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('This Week', $weekBookings)
                ->description('Bookings this week')
                ->icon('heroicon-o-clock')
                ->color('success'),

            Stat::make('Monthly Revenue', '$' . number_format($monthRevenue, 2))
                ->description($revenueChange >= 0 ? "+{$revenueChange}% vs last month" : "{$revenueChange}% vs last month")
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger'),

            Stat::make('Pending Review', $pendingBookings)
                ->description('Awaiting confirmation')
                ->icon('heroicon-o-exclamation-circle')
                ->color($pendingBookings > 0 ? 'warning' : 'success'),
        ];
    }
}