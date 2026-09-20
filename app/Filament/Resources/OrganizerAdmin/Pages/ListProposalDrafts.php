<?php

namespace App\Filament\Resources\OrganizerAdmin\Pages;

use App\Filament\Resources\OrganizerAdmin\ProposalDraftResource;
use Filament\Resources\Pages\ListRecords;

class ListProposalDrafts extends ListRecords
{
    protected static string $resource = ProposalDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Buat Draft')
                ->url(function () {
                    $event = session('selected_event') ?? request()->query('event') ?? request()->input('tableFilters.id_event.value');
                    if (blank($event)) {
                        \Filament\Notifications\Notification::make()->title('Pilih Event dulu')->body('Pilih Event di filter atau klik Daftar Draft dari menu Events sebelum membuat draft.')->warning()->send();
                        return null;
                    }
                    return ProposalDraftResource::getUrl('create', ['event' => $event]);
                }),
        ];
    }
}

