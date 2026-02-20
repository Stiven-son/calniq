<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Services\GoogleCalendarService;
use App\Services\WebhookService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Bookings';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('customer_phone')
                            ->tel()
                            ->required(),
                        Forms\Components\Select::make('customer_type')
                            ->options([
                                'residential' => 'Residential',
                                'commercial' => 'Commercial',
                            ])
                            ->default('residential'),
                    ])->columns(2),

                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('address_unit')
                            ->label('Unit/Apt'),
                        Forms\Components\TextInput::make('city'),
                        Forms\Components\TextInput::make('state'),
                        Forms\Components\TextInput::make('zip'),
                    ])->columns(4),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->required(),
                        Forms\Components\TimePicker::make('scheduled_time_start')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TimePicker::make('scheduled_time_end')
                            ->required()
                            ->seconds(false),
                    ])->columns(3),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('total')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('promo_code_used')
                            ->label('Promo Code')
                            ->disabled(),
                    ])->columns(4),

                Forms\Components\Section::make('Status & Notes')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('message')
                            ->label('Customer Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Ref #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Booking $record): string => $record->customer_phone ?? ''),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_time_start')
                    ->label('Time')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('g:i A')),

                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('promo_code_used')
                    ->label('Promo')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),

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
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => $record->status === 'pending')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'confirmed']);

                        // Dispatch webhook
                        app(WebhookService::class)->dispatch($record, 'booking.confirmed');
                    }),

                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => in_array($record->status, ['pending', 'confirmed']))
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'cancelled']);

                        // Delete Google Calendar event
                        if ($record->google_event_id && $record->location?->google_calendar_id) {
                            try {
                                $calendarService = app(GoogleCalendarService::class);
                                $calendarService->deleteEvent(
								$record->google_event_id,
								$record->location->google_calendar_id
								);
                                $record->update(['google_event_id' => null]);
                            } catch (\Exception $e) {
                                \Log::error('Failed to delete GCal event on cancel: ' . $e->getMessage());
                            }
                        }

                        // Dispatch webhook
                        app(WebhookService::class)->dispatch($record, 'booking.cancelled');
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Booking Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference_number')
                            ->label('Reference'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('scheduled_date')
                            ->date('M d, Y'),
                        Infolists\Components\TextEntry::make('scheduled_time_start')
                            ->label('Time')
                            ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('g:i A')),
                    ])->columns(4),

                Infolists\Components\Section::make('Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_name'),
                        Infolists\Components\TextEntry::make('customer_email'),
                        Infolists\Components\TextEntry::make('customer_phone'),
                        Infolists\Components\TextEntry::make('customer_type')
                            ->badge(),
                    ])->columns(4),

                Infolists\Components\Section::make('Address')
                    ->schema([
                        Infolists\Components\TextEntry::make('address'),
                        Infolists\Components\TextEntry::make('city'),
                        Infolists\Components\TextEntry::make('state'),
                        Infolists\Components\TextEntry::make('zip'),
                    ])->columns(4),

                Infolists\Components\Section::make('Services Ordered')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('service_name')
                                    ->label('Service'),
                                Infolists\Components\TextEntry::make('quantity'),
                                Infolists\Components\TextEntry::make('unit_price')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make('total_price')
                                    ->money('USD')
                                    ->weight('bold'),
                            ])
                            ->columns(4),
                    ]),

                Infolists\Components\Section::make('Pricing')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('discount_amount')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('total')
                            ->money('USD')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('promo_code_used')
                            ->label('Promo Code')
                            ->placeholder('None'),
                    ])->columns(4),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('message')
                            ->placeholder('No notes'),
                    ])
                    ->visible(fn (Booking $record) => !empty($record->message)),

                Infolists\Components\Section::make('Tracking')
                    ->schema([
                        Infolists\Components\TextEntry::make('utm_source')->placeholder('—'),
                        Infolists\Components\TextEntry::make('utm_medium')->placeholder('—'),
                        Infolists\Components\TextEntry::make('utm_campaign')->placeholder('—'),
                        Infolists\Components\TextEntry::make('source')->placeholder('—'),
                        Infolists\Components\TextEntry::make('ga_client_id')->label('GA Client ID')->placeholder('—'),
                        Infolists\Components\TextEntry::make('gclid')->label('GCLID')->placeholder('—'),
                        Infolists\Components\TextEntry::make('gbraid')->label('GBRAID')->placeholder('—'),
                        Infolists\Components\TextEntry::make('wbraid')->label('WBRAID')->placeholder('—'),
                    ])->columns(4)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
