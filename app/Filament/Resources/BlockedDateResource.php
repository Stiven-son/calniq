<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockedDateResource\Pages;
use App\Models\BlockedDate;
use App\Models\Location;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlockedDateResource extends Resource
{
    protected static ?string $model = BlockedDate::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Blocked Dates';
    protected static ?string $navigationGroup = 'Schedule';
    protected static ?int $navigationSort = 20;
    protected static bool $isScopedToTenant = false;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('blocked_dates');
    }

    public static function form(Form $form): Form
    {
        $project = Filament::getTenant();
        $locations = Location::where('project_id', $project->id)->pluck('name', 'id');

        return $form
            ->schema([
                Forms\Components\Section::make('Block a Date')
                    ->description('Prevent bookings on specific dates (holidays, vacations, etc.).')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->options($locations)
                            ->required()
                            ->default($locations->keys()->first()),
                        Forms\Components\DatePicker::make('blocked_date')
                            ->label('Date')
                            ->required()
                            ->minDate(now())
                            ->native(false)
                            ->displayFormat('M d, Y'),
                        Forms\Components\TextInput::make('reason')
                            ->label('Reason')
                            ->placeholder('Holiday, Vacation, Maintenance...')
                            ->maxLength(255),
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
                Tables\Columns\TextColumn::make('blocked_date')
                    ->label('Date')
                    ->date('M d, Y (l)')
                    ->sortable()
                    ->color(fn ($record) => $record->blocked_date < now() ? 'gray' : 'danger'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->color('warning')
                    ->default('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('blocked_date', 'asc')
            ->filters([
                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->query(fn (Builder $query) => $query->where('blocked_date', '>=', now()))
                    ->default(true),
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
            'index' => Pages\ListBlockedDates::route('/'),
            'create' => Pages\CreateBlockedDate::route('/create'),
            'edit' => Pages\EditBlockedDate::route('/{record}/edit'),
        ];
    }
}