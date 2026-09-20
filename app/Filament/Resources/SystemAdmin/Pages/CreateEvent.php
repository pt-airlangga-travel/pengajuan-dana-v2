<?php

namespace App\Filament\Resources\SystemAdmin\Pages;

use App\Filament\Resources\SystemAdmin\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $institution = $data['id_institution'] ?? '000';
        $prefix = date('Ym') . substr(str_pad($institution, 3, '0', STR_PAD_LEFT), 0, 3);
        $count = \App\Models\Event::where('event_defined_id', 'like', $prefix . '%')->count() + 1;
        $data['event_defined_id'] = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
        return $data;
    }
}

