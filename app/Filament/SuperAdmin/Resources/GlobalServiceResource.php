<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\GlobalServiceResource\Pages;
use App\Models\GlobalCategory;
use App\Models\GlobalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GlobalServiceResource extends Resource
{
    protected static ?string $model = GlobalService::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Services';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Service';
    protected static ?string $pluralModelLabel = 'Services';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\Select::make('global_category_id')
                            ->label('Category')
                            ->relationship('globalCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('slug')->required(),
                            ]),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Loveseat (2 seats)'),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->placeholder('e.g. Deep Steam Cleaning')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('default_price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('79.00')
                            ->helperText('Default price. Clients can override per-project.'),
                        Forms\Components\Select::make('price_type')
                            ->options([
                                'fixed' => 'Fixed Price',
                                'per_unit' => 'Per Unit',
                                'per_sqft' => 'Per Sq Ft',
                            ])
                            ->default('fixed')
                            ->required(),
                        Forms\Components\TextInput::make('price_unit')
                            ->placeholder('e.g. seat, room, sq ft')
                            ->helperText('Label shown after price (for per_unit/per_sqft)'),
                    ])->columns(3),

                Forms\Components\Section::make('Constraints')
                    ->schema([
                        Forms\Components\TextInput::make('min_quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        Forms\Components\TextInput::make('max_quantity')
                            ->numeric()
                            ->default(10),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->default(60)
                            ->suffix('min')
                            ->helperText('For time slot calculation'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Order within category'),
                    ])->columns(4),

                Forms\Components\Section::make('Display')
                    ->schema([
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Service Image')
                            ->image()
                            ->directory('global-services')
                            ->imagePreviewHeight('150')
                            ->maxSize(2048)
                            ->helperText('PNG with transparent background recommended. Clients can override.'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active — available for client selection')
                            ->helperText('Inactive services are hidden from clients'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('global_category_id')
            ->defaultGroup('globalCategory.name')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('')
                    ->square()
                    ->size(50)
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=?&background=e5e7eb&color=9ca3af'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (GlobalService $record): ?string => $record->description),

                Tables\Columns\TextColumn::make('globalCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('default_price')
                    ->money('USD')
                    ->sortable()
                    ->label('Default Price'),

                Tables\Columns\TextColumn::make('price_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'success',
                        'per_unit' => 'info',
                        'per_sqft' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),

                Tables\Columns\TextColumn::make('projects_using')
                    ->label('Projects')
                    ->getStateUsing(fn (GlobalService $record) => $record->projectServices()->count())
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('global_category_id')
                    ->label('Category')
                    ->relationship('globalCategory', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('price_type')
                    ->options([
                        'fixed' => 'Fixed',
                        'per_unit' => 'Per Unit',
                        'per_sqft' => 'Per Sq Ft',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->excludeAttributes(['sort_order'])
                    ->beforeReplicaSaved(function (GlobalService $replica): void {
                        $replica->name = $replica->name . ' (copy)';
                        $replica->sort_order = GlobalService::where('global_category_id', $replica->global_category_id)->max('sort_order') + 1;
                    }),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (GlobalService $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (GlobalService $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (GlobalService $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (GlobalService $record) => $record->update(['is_active' => !$record->is_active])),
                Tables\Actions\DeleteAction::make()
                    ->before(function (GlobalService $record, Tables\Actions\DeleteAction $action) {
                        if ($record->projectServices()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot delete')
                                ->body('This service is used by ' . $record->projectServices()->count() . ' project(s).')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
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
            'index' => Pages\ListGlobalServices::route('/'),
            'create' => Pages\CreateGlobalService::route('/create'),
            'edit' => Pages\EditGlobalService::route('/{record}/edit'),
        ];
    }
}
