<?php

namespace App\Filament\Resources\ProjectServiceResource\Pages;

use App\Filament\Resources\ProjectServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectService extends EditRecord
{
    protected static string $resource = ProjectServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Remove from Project'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
