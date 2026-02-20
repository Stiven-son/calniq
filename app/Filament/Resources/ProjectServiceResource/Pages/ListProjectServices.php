<?php

namespace App\Filament\Resources\ProjectServiceResource\Pages;

use App\Filament\Pages\ServiceSetupWizard;
use App\Filament\Resources\ProjectServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectServices extends ListRecords
{
    protected static string $resource = ProjectServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addMore')
                ->label('Add More Services')
                ->url(ServiceSetupWizard::getUrl())
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
