<?php

namespace App\Filament\Resources\TeamMemberResource\Pages;

use App\Filament\Resources\TeamMemberResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditTeamMember extends EditRecord
{
    protected static string $resource = TeamMemberResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load current project assignments for the checkbox list
        $data['project_ids'] = $this->record->assignedProjects()->pluck('projects.id')->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $currentUser = auth()->user();

        if ($currentUser->isOwner()) {
            // Owner can reassign projects
            $projectIds = $this->data['project_ids'] ?? [];
            $role = $record->role;

            // Sync: detach all, re-attach with current role
            $syncData = [];
            foreach ($projectIds as $projectId) {
                $syncData[$projectId] = ['role' => $role];
            }
            $record->assignedProjects()->sync($syncData);
        } else {
            // Admin: update role in current project only
            $project = Filament::getTenant();
            if ($project) {
                $record->assignedProjects()->updateExistingPivot($project->id, [
                    'role' => $record->role,
                ]);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => !$this->record->is_owner && $this->record->id !== auth()->id())
                ->before(function () {
                    $this->record->assignedProjects()->detach();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
