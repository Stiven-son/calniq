<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\GlobalCategoryResource\Pages;
use App\Models\GlobalCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class GlobalCategoryResource extends Resource
{
    protected static ?string $model = GlobalCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Category';
    protected static ?string $pluralModelLabel = 'Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set, ?string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->placeholder('e.g. Upholstery Cleaning'),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in Pricing Widget embed code and API URLs'),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Brief description for admin reference')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Display')
                    ->schema([
                        Forms\Components\FileUpload::make('icon_url')
                            ->label('Category Icon')
                            ->image()
                            ->directory('global-categories')
                            ->imagePreviewHeight('150')
                            ->maxSize(2048)
                            ->helperText('PNG with transparent background, 200×200px recommended'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower = shown first'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(false)
                            ->label('Active — visible to clients')
                            ->helperText('Keep inactive while preparing services and icons'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('icon_url')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=?&background=e5e7eb&color=9ca3af'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (GlobalCategory $record): string => $record->slug),

                Tables\Columns\TextColumn::make('global_services_count')
                    ->counts('globalServices')
                    ->label('Services')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('active_services')
                    ->label('Active')
                    ->getStateUsing(fn (GlobalCategory $record) => $record->globalServices()->active()->count())
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('projects_using')
                    ->label('Projects')
                    ->getStateUsing(fn (GlobalCategory $record) => $record->projectCategories()->count())
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
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manageServices')
                    ->label('Services')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn (GlobalCategory $record): string =>
                        GlobalServiceResource::getUrl('index', [
                            'tableFilters[global_category_id][value]' => $record->id,
                        ])
                    ),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (GlobalCategory $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (GlobalCategory $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (GlobalCategory $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (GlobalCategory $record) => $record->update(['is_active' => !$record->is_active])),
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
            'index' => Pages\ListGlobalCategories::route('/'),
            'create' => Pages\CreateGlobalCategory::route('/create'),
            'edit' => Pages\EditGlobalCategory::route('/{record}/edit'),
        ];
    }
}
