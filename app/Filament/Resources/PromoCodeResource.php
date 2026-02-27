<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use App\Models\ProjectService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Promo Codes';
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('promo_codes');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Promo Code')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g. SAVE10')
                            ->helperText('Customers will enter this code')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),
                        Forms\Components\TextInput::make('description')
                            ->maxLength(255)
                            ->placeholder('e.g. 10% off for new customers')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Discount')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->options([
                                'percent' => 'Percentage (%)',
                                'fixed' => 'Fixed Amount ($)',
                            ])
                            ->required()
                            ->live()
                            ->default('percent'),
                        Forms\Components\TextInput::make('discount_value')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix(fn (Get $get): string => $get('discount_type') === 'fixed' ? '$' : '')
                            ->suffix(fn (Get $get): string => $get('discount_type') === 'percent' ? '%' : '')
                            ->helperText(fn (Get $get): string => match ($get('discount_type')) {
                                'percent' => 'e.g. 10 = 10% off',
                                'fixed' => 'e.g. 25 = $25 off',
                                default => '',
                            })
                            ->maxValue(fn (Get $get): ?int => $get('discount_type') === 'percent' ? 100 : null),
                    ])->columns(2),

                Forms\Components\Section::make('Usage Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_uses')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Unlimited')
                            ->helperText('Leave empty for unlimited uses'),
                        Forms\Components\TextInput::make('current_uses')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Times used so far'),
                        Forms\Components\TextInput::make('min_order_amount')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('No minimum')
                            ->helperText('Minimum subtotal required'),
                    ])->columns(3),

                Forms\Components\Section::make('Validity Period')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->placeholder('Immediately')
                            ->helperText('Leave empty to start now'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiry Date')
                            ->placeholder('Never')
                            ->helperText('Leave empty for no expiry')
                            ->after('starts_at'),
                    ])->columns(2),

                Forms\Components\Section::make('Apply to Services')
                    ->schema([
                        Forms\Components\Select::make('applicable_services')
                            ->label('Applicable Services')
                            ->multiple()
                            ->options(function () {
                                $project = Filament::getTenant();
                                return ProjectService::where('project_id', $project->id)
                                    ->where('is_active', true)
                                    ->with('globalService')
                                    ->get()
                                    ->pluck(function ($ps) {
                                        return $ps->custom_name ?? $ps->globalService->name;
                                    }, 'global_service_id');
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty to apply to ALL services'),
                    ]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active')
                            ->helperText('Inactive codes cannot be used by customers'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Code copied!'),

                Tables\Columns\TextColumn::make('discount_display')
                    ->label('Discount')
                    ->state(fn (PromoCode $record): string => $record->discount_type === 'percent'
                        ? "{$record->discount_value}%"
                        : '$' . number_format($record->discount_value, 2)
                    )
                    ->badge()
                    ->color(fn (PromoCode $record): string => $record->discount_type === 'percent' ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('usage')
                    ->label('Usage')
                    ->state(fn (PromoCode $record): string => $record->max_uses
                        ? "{$record->current_uses} / {$record->max_uses}"
                        : "{$record->current_uses} / ∞"
                    ),

                Tables\Columns\TextColumn::make('min_order_amount')
                    ->label('Min Order')
                    ->money('USD')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->date('M d, Y')
                    ->placeholder('Immediately'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date('M d, Y')
                    ->placeholder('Never')
                    ->color(fn (PromoCode $record): string => 
                        $record->expires_at && $record->expires_at->isPast() ? 'danger' : 'gray'
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('discount_type')
                    ->options([
                        'percent' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ]),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
