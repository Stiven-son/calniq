<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeSlotResource\Pages;
use App\Models\TimeSlot;
use App\Models\Location;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimeSlotResource extends Resource
{
    protected static ?string $model = TimeSlot::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Time Slots';
    protected static ?string $navigationGroup = 'Schedule';
    protected static ?int $navigationSort = 10;
    protected static bool $isScopedToTenant = false;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('time_slots');
    }

    public static function form(Form $form): Form
    {
        $project = Filament::getTenant();
        $locations = Location::where('project_id', $project->id)->pluck('name', 'id');

        return $form
            ->schema([
                Forms\Components\Section::make('Time Slot')
                    ->description('Define available booking windows for each day.')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->options($locations)
                            ->required()
                            ->default($locations->keys()->first()),
                        Forms\Components\Select::make('day_of_week')
                            ->label('Day of Week')
                            ->options([
                                0 => 'Sunday',
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                            ])
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Start Time')
                                    ->seconds(false)
                                    ->required(),
                                Forms\Components\TimePicker::make('end_time')
                                    ->label('End Time')
                                    ->seconds(false)
                                    ->required()
                                    ->after('start_time'),
                            ]),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn (int $state): string => [
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ][$state] ?? '')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        0 => 'danger',
                        6 => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start')
                    ->time('g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('End')
                    ->time('g:i A'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('day_of_week')
            ->defaultGroup('day_of_week')
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->label('Day')
                    ->options([
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $project = Filament::getTenant();
        $locationIds = Location::where('project_id', $project->id)->pluck('id');

        return parent::getEloquentQuery()
            ->whereIn('location_id', $locationIds);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeSlots::route('/'),
            'create' => Pages\CreateTimeSlot::route('/create'),
            'edit' => Pages\EditTimeSlot::route('/{record}/edit'),
        ];
    }
}