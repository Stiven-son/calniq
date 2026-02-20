<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Ref #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('project.tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_time_start')
                    ->label('Time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('promo_code_used')
                    ->label('Promo')
                    ->placeholder('—')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Project')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Booking Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference_number')->label('Reference'),
                        Infolists\Components\TextEntry::make('project.name')->label('Project'),
                        Infolists\Components\TextEntry::make('project.tenant.name')->label('Tenant'),
                        Infolists\Components\TextEntry::make('status')->badge(),
                        Infolists\Components\TextEntry::make('scheduled_date')->date(),
                        Infolists\Components\TextEntry::make('scheduled_time_start')->time('H:i'),
                    ])->columns(3),

                Infolists\Components\Section::make('Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_name'),
                        Infolists\Components\TextEntry::make('customer_email'),
                        Infolists\Components\TextEntry::make('customer_phone'),
                        Infolists\Components\TextEntry::make('address')->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Pricing')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')->money('usd'),
						Infolists\Components\TextEntry::make('discount_amount')->money('usd'),
						Infolists\Components\TextEntry::make('total')->money('usd')->weight('bold'),
						Infolists\Components\TextEntry::make('promo_code_used')->placeholder('None'),
                    ])->columns(4),

                Infolists\Components\Section::make('Tracking')
                    ->schema([
                        Infolists\Components\TextEntry::make('utm_source')->placeholder('—'),
                        Infolists\Components\TextEntry::make('utm_medium')->placeholder('—'),
                        Infolists\Components\TextEntry::make('utm_campaign')->placeholder('—'),
                        Infolists\Components\TextEntry::make('ga_client_id')->placeholder('—'),
                        Infolists\Components\TextEntry::make('gclid')->placeholder('—'),
                        Infolists\Components\TextEntry::make('gbraid')->placeholder('—'),
                    ])->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}