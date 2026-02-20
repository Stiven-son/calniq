<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
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

                Forms\Components\Section::make('Google Calendar')
                    ->icon('heroicon-o-calendar')
                    ->description('Connect a Google Calendar to check availability and create events.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('google_calendar_id')
                            ->label('Google Calendar ID')
                            ->placeholder('example@gmail.com or calendar ID')
                            ->maxLength(255)
                            ->helperText('The calendar ID used for availability checking and event creation. Usually an email address.'),
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
                Tables\Columns\IconColumn::make('google_calendar_id')
                    ->label('Calendar')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->tooltip(fn ($state) => $state ? "Connected: {$state}" : 'Not connected'),
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
