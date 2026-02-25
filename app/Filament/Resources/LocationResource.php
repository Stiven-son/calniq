<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use App\Services\GoogleCalendarService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Locations';
    protected static ?string $navigationGroup = 'Schedule';
    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('locations');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Location Details')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Location Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Main Office, Downtown Branch'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->label('Street Address')
                                    ->rows(2)
                                    ->placeholder('123 Main Street'),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('city')
                                            ->maxLength(100)
                                            ->placeholder('Raleigh'),
                                        Forms\Components\TextInput::make('state')
                                            ->maxLength(50)
                                            ->placeholder('NC'),
                                        Forms\Components\TextInput::make('zip')
                                            ->label('ZIP Code')
                                            ->maxLength(20)
                                            ->placeholder('27601'),
                                        Forms\Components\TextInput::make('country')
                                            ->maxLength(2)
                                            ->default('US')
                                            ->placeholder('US'),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Booking Capacity')
                    ->icon('heroicon-o-user-group')
                    ->description('Control how many bookings can overlap on the same time slot.')
                    ->schema([
                        Forms\Components\TextInput::make('max_concurrent_bookings')
                            ->label('Max Concurrent Bookings')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(50)
                            ->required()
                            ->suffix('per slot')
                            ->helperText('How many crews/teams can work at the same time. Set to 1 if only one booking per time slot is allowed.'),
                    ]),

                // --- Google Calendar OAuth Section ---
                Forms\Components\Section::make('Google Calendar')
                    ->icon('heroicon-o-calendar')
                    ->description('Connect your Google Calendar to sync bookings and check availability.')
                    ->schema([

                        // Status indicator
                        Forms\Components\Placeholder::make('google_calendar_status')
                            ->label('Connection Status')
                            ->content(function (?Location $record) {
                                if (!$record) {
                                    return '⚠️ Save the location first, then connect Google Calendar.';
                                }
                                if ($record->google_refresh_token) {
                                    $calendarName = $record->google_calendar_name ?: $record->google_calendar_id;
                                    return "✅ Connected — {$calendarName}";
                                }
                                return '❌ Not connected';
                            }),

                        // Connect button (only when NOT connected and record exists)
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('connect_google_calendar')
                                ->label('Connect Google Calendar')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->color('primary')
                                ->size('lg')
                                ->url(fn (?Location $record) => $record
                                    ? route('google-calendar.redirect', ['location_id' => $record->id])
                                    : '#')
                                ->openUrlInNewTab(false)
                                ->visible(fn (?Location $record) => $record && !$record->google_refresh_token),
                        ]),

                        // Calendar picker + Disconnect (only when connected)
                        Forms\Components\Select::make('google_calendar_id')
                            ->label('Select Calendar')
                            ->options(function (?Location $record) {
                                if (!$record || !$record->google_refresh_token) {
                                    return [];
                                }
                                try {
                                    return app(GoogleCalendarService::class)->listCalendars($record);
                                } catch (\Exception $e) {
                                    return [$record->google_calendar_id => $record->google_calendar_name ?: $record->google_calendar_id];
                                }
                            })
                            ->searchable()
                            ->helperText('Choose which calendar to use for booking events and availability.')
                            ->visible(fn (?Location $record) => $record && $record->google_refresh_token)
                            ->afterStateUpdated(function ($state, ?Location $record) {
                                // Update calendar name when selection changes
                                if ($record && $state) {
                                    try {
                                        $calendars = app(GoogleCalendarService::class)->listCalendars($record);
                                        $record->google_calendar_name = $calendars[$state] ?? $state;
                                    } catch (\Exception $e) {
                                        // Keep existing name
                                    }
                                }
                            })
                            ->live(),

                        // Disconnect button
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('disconnect_google_calendar')
                                ->label('Disconnect Google Calendar')
                                ->icon('heroicon-o-x-mark')
                                ->color('danger')
                                ->size('sm')
                                ->requiresConfirmation()
                                ->modalHeading('Disconnect Google Calendar?')
                                ->modalDescription('This will remove the connection. Existing booking events in Google Calendar won\'t be deleted, but new bookings won\'t sync.')
                                ->modalSubmitActionLabel('Yes, disconnect')
                                ->action(function (Location $record) {
                                    // Optionally revoke token
                                    try {
                                        $client = new \Google\Client();
                                        $client->setClientId(config('services.google.client_id'));
                                        $client->setClientSecret(config('services.google.client_secret'));
                                        $client->revokeToken(decrypt($record->google_refresh_token));
                                    } catch (\Exception $e) {
                                        // Not critical
                                    }

                                    $record->update([
                                        'google_refresh_token' => null,
                                        'google_calendar_id' => null,
                                        'google_calendar_name' => null,
                                    ]);

                                    // Invalidate calendar list cache
                                    app(GoogleCalendarService::class)->invalidateCalendarListCache($record);

                                    Notification::make()
                                        ->title('Google Calendar disconnected')
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn (?Location $record) => $record && $record->google_refresh_token),
                        ]),

                        // Test connection result
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('test_google_connection')
                                ->label('Test Connection')
                                ->icon('heroicon-o-signal')
                                ->color('gray')
                                ->size('sm')
                                ->action(function (Location $record) {
                                    $result = app(GoogleCalendarService::class)->testConnection($record);

                                    if ($result['success']) {
                                        Notification::make()
                                            ->title('Connection OK')
                                            ->body("Calendar: {$result['calendar_name']}, Timezone: {$result['timezone']}")
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Connection Failed')
                                            ->body($result['message'])
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(fn (?Location $record) => $record && $record->google_refresh_token && $record->google_calendar_id),
                        ]),
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive locations are hidden from the booking widget.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Location')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('city')
                    ->label('City'),
                Tables\Columns\TextColumn::make('state')
                    ->label('State'),
                Tables\Columns\TextColumn::make('max_concurrent_bookings')
                    ->label('Capacity')
                    ->badge()
                    ->suffix(' per slot')
                    ->color(fn (int $state): string => match(true) {
                        $state >= 3 => 'success',
                        $state >= 2 => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('google_calendar_name')
                    ->label('Google Calendar')
                    ->default('Not connected')
                    ->icon(fn ($record) => $record->google_refresh_token ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn ($record) => $record->google_refresh_token ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
