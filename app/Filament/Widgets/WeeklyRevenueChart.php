<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class WeeklyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue (Last 4 Weeks)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $projectId = Filament::getTenant()->id;
        $labels = [];
        $data = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $labels[] = $weekStart->format('M d');

            $data[] = (float) Booking::where('project_id', $projectId)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
                ->sum('total');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => '#10b981',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}