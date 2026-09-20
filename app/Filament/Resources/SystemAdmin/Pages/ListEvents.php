<?php

namespace App\Filament\Resources\SystemAdmin\Pages;

use App\Filament\Resources\SystemAdmin\EventResource;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;
}

