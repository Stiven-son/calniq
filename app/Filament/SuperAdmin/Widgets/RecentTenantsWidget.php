<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Booking;
use App\Models\Tenant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTenantsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Tenants';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->size('sm'),
                Tables\Columns\BadgeColumn::make('plan')
                    ->colors([
                        'gray' => 'starter',
                        'warning' => 'pro',
                        'success' => 'agency',
                    ]),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects'),
                Tables\Columns\TextColumn::make('bookings')
                    ->label('Bookings')
                    ->getStateUsing(function (Tenant $record): int {
                        return Booking::whereHas('project', fn ($q) => $q->where('tenant_id', $record->id))->count();
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->since(),
            ])
            ->paginated(false);
    }
}