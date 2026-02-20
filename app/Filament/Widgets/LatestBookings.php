<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestBookings extends TableWidget
{
    protected static ?string $heading = 'Latest Bookings';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $project = Filament::getTenant();

        return $table
            ->query(
                Booking::where('project_id', $project->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Ref #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Date')
                    ->date('M d, Y'),
                Tables\Columns\TextColumn::make('scheduled_time_start')
                    ->label('Time')
                    ->time('g:i A'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('usd'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Booking $record) => route('filament.admin.resources.bookings.view', [
                        'tenant' => $project->slug,
                        'record' => $record,
                    ]))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}