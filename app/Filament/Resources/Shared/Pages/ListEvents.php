<?php

namespace App\Filament\Resources\Shared\Pages;

use App\Filament\Resources\Shared\EventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Event'),
        ];
    }
}

