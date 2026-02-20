<?php

namespace App\Filament\SuperAdmin\Resources\GlobalServiceResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGlobalService extends EditRecord
{
    protected static string $resource = GlobalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (EditGlobalService $livewire) {
                    $record = $livewire->getRecord();
                    if ($record->projectServices()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Cannot delete')
                            ->body('This service is used by ' . $record->projectServices()->count() . ' project(s). Remove it from all projects first.')
                            ->danger()
                            ->send();
                        $livewire->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
