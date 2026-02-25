<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectCategoryResource\Pages;
use App\Models\ProjectCategory;
use App\Models\ProjectService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectCategoryResource extends Resource
{
    protected static ?string $model = ProjectCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'My Categories';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Category';
    protected static ?string $pluralModelLabel = 'My Categories';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('categories');
    }

    public static function canCreate(): bool
    {
        return false; // Categories are added via Service Setup wizard
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('globalCategory.icon_url')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=?&background=e5e7eb&color=9ca3af'),

                Tables\Columns\TextColumn::make('globalCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (ProjectCategory $record): string => $record->globalCategory->slug ?? ''),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->getStateUsing(function (ProjectCategory $record): int {
                        return ProjectService::where('project_id', $record->project_id)
                            ->whereHas('globalService', fn ($q) => $q->where('global_category_id', $record->global_category_id))
                            ->count();
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('active_services')
                    ->label('Active')
                    ->getStateUsing(function (ProjectCategory $record): int {
                        return ProjectService::where('project_id', $record->project_id)
                            ->where('is_active', true)
                            ->whereHas('globalService', fn ($q) => $q->where('global_category_id', $record->global_category_id))
                            ->count();
                    })
                    ->badge()
                    ->color('success'),

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
                Tables\Actions\Action::make('viewServices')
                    ->label('Services')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn (ProjectCategory $record): string =>
                        ProjectServiceResource::getUrl('index', [
                            'tableGrouping' => 'globalService.globalCategory.name',
                        ])
                    ),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (ProjectCategory $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (ProjectCategory $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (ProjectCategory $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (ProjectCategory $record) => $record->update(['is_active' => !$record->is_active])),
                Tables\Actions\Action::make('removeCategory')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(fn (ProjectCategory $record) =>
                        'This will remove "' . $record->globalCategory->name . '" and all its services from your project.'
                    )
                    ->action(function (ProjectCategory $record): void {
                        // Delete all project_services in this category
                        ProjectService::where('project_id', $record->project_id)
                            ->whereHas('globalService', fn ($q) => $q->where('global_category_id', $record->global_category_id))
                            ->delete();

                        $record->delete();
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No categories yet')
            ->emptyStateDescription('Add service categories to start building your widget.')
            ->emptyStateIcon('heroicon-o-folder')
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
            'index' => Pages\ListProjectCategories::route('/'),
        ];
    }
}
