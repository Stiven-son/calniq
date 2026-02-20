<?php

namespace App\Filament\Resources\ProjectCategoryResource\Pages;

use App\Filament\Pages\ServiceSetupWizard;
use App\Filament\Resources\ProjectCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectCategories extends ListRecords
{
    protected static string $resource = ProjectCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addMore')
                ->label('Add More Categories')
                ->url(ServiceSetupWizard::getUrl())
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
