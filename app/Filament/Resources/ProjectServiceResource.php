<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectServiceResource\Pages;
use App\Models\ProjectService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectServiceResource extends Resource
{
    protected static ?string $model = ProjectService::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'My Services';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Service';
    protected static ?string $pluralModelLabel = 'My Services';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('services');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\Placeholder::make('category_info')
                            ->label('Category')
                            ->content(fn (ProjectService $record): string =>
                                $record->globalService->globalCategory->name
                            ),
                        Forms\Components\TextInput::make('custom_name')
                            ->label('Name')
                            ->maxLength(255)
                            ->placeholder(fn (ProjectService $record): string =>
                                $record->globalService->name
                            )
                            ->helperText('Leave empty to use the default name.'),
                        Forms\Components\Textarea::make('custom_description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder(fn (ProjectService $record): ?string =>
                                $record->globalService->description
                            )
                            ->helperText('Leave empty to use the default description.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('custom_price')
                            ->label('Price')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder(fn (ProjectService $record): string =>
                                number_format($record->globalService->default_price, 2)
                            )
                            ->helperText(fn (ProjectService $record): string =>
                                'Default: $' . number_format($record->globalService->default_price, 2) .
                                '. Leave empty to use default.'
                            ),
                        Forms\Components\Placeholder::make('price_type_info')
                            ->label('Price Type')
                            ->content(fn (ProjectService $record): string =>
                                match ($record->globalService->price_type) {
                                    'fixed' => 'Fixed Price',
                                    'per_unit' => 'Per Unit (' . ($record->globalService->price_unit ?: 'unit') . ')',
                                    'per_sqft' => 'Per Sq Ft',
                                    default => $record->globalService->price_type,
                                }
                            ),
                        Forms\Components\Placeholder::make('duration_info')
                            ->label('Duration')
                            ->content(fn (ProjectService $record): string =>
                                $record->globalService->duration_minutes . ' min'
                            ),
                    ])->columns(3),

                Forms\Components\Section::make('Display')
                    ->schema([
                        Forms\Components\FileUpload::make('custom_image')
                            ->label('Custom Image')
                            ->image()
                            ->directory('project-services')
                            ->imagePreviewHeight('150')
                            ->maxSize(2048)
                            ->helperText('Leave empty to use the default image. Upload to override.'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Order within your widget'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active — visible in your widget')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->defaultGroup('globalService.globalCategory.name')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('effective_image_url')
                    ->label('')
                    ->square()
                    ->size(40)
                    ->getStateUsing(function (ProjectService $record): ?string {
                        if ($record->custom_image) {
                            return $record->custom_image;
                        }
                        return $record->globalService->image_url ?? null;
                    })
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=?&background=e5e7eb&color=9ca3af'),

                Tables\Columns\TextColumn::make('effective_name')
                    ->label('Service')
                    ->searchable(['custom_name'])
                    ->sortable()
                    ->weight('bold')
                    ->getStateUsing(fn (ProjectService $record): string => $record->effective_name)
                    ->description(fn (ProjectService $record): ?string => $record->effective_description)
                    ->icon(fn (ProjectService $record): ?string =>
                        $record->custom_name ? 'heroicon-s-pencil-square' : null
                    )
                    ->iconColor('warning'),

                Tables\Columns\TextColumn::make('globalService.globalCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('globalService.default_price')
                    ->label('Default')
                    ->money('USD')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('custom_price')
                    ->label('Your Price')
                    ->money('USD')
                    ->sortable()
                    ->placeholder('— default —')
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('globalService.price_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'success',
                        'per_unit' => 'info',
                        'per_sqft' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('globalService.globalCategory', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (ProjectService $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (ProjectService $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (ProjectService $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (ProjectService $record) => $record->update(['is_active' => !$record->is_active])),
                Tables\Actions\Action::make('resetAll')
                    ->label('Reset to Default')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('This will reset name, description, price and image to catalog defaults.')
                    ->action(fn (ProjectService $record) => $record->update([
                        'custom_name' => null,
                        'custom_description' => null,
                        'custom_price' => null,
                        'custom_image' => null,
                    ]))
                    ->visible(fn (ProjectService $record) =>
                        $record->custom_name !== null ||
                        $record->custom_description !== null ||
                        $record->custom_price !== null ||
                        $record->custom_image !== null
                    ),
                Tables\Actions\DeleteAction::make()
                    ->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Remove Selected'),
                ]),
            ])
            ->emptyStateHeading('No services yet')
            ->emptyStateDescription('Set up your services to start accepting bookings.')
            ->emptyStateIcon('heroicon-o-sparkles')
            ->emptyStateActions([
                Tables\Actions\Action::make('setup')
                    ->label('Set Up Services')
                    ->url(fn (): string => \App\Filament\Pages\ServiceSetupWizard::getUrl())
                    ->icon('heroicon-o-sparkles'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectServices::route('/'),
            'edit' => Pages\EditProjectService::route('/{record}/edit'),
        ];
    }
}
