<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class BookingStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Bookings by Status';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $projectId = Filament::getTenant()->id;

        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        $counts = [];

        foreach ($statuses as $status) {
            $counts[] = Booking::where('project_id', $projectId)
                ->where('status', $status)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'data' => $counts,
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
                ],
            ],
            'labels' => ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}