<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\ProjectResource\Pages;
use App\Models\Booking;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('tenant.plan')
                    ->label('Plan')
                    ->colors([
                        'gray' => 'starter',
                        'warning' => 'pro',
                        'success' => 'agency',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->counts('bookings')
                    ->label('Bookings')
                    ->sortable(),
                Tables\Columns\TextColumn::make('revenue')
                    ->label('Revenue')
                    ->getStateUsing(function (Project $record): string {
                        $revenue = Booking::where('project_id', $record->id)
                            ->whereIn('status', ['confirmed', 'completed'])
                            ->sum('total');
                        return '$' . number_format($revenue, 2);
                    }),
                Tables\Columns\TextColumn::make('timezone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->label('Tenant')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('openAdmin')
                    ->label('Open Admin')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Project $record): string => "/admin/{$record->slug}")
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('openWidget')
                    ->label('Widget')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Project $record): string => "/widget-demo?tenant={$record->slug}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
        ];
    }
}