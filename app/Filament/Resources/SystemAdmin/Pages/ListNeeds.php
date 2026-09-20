<?php

namespace App\Filament\Resources\SystemAdmin\Pages;

use App\Filament\Resources\SystemAdmin\NeedResource;
use Filament\Resources\Pages\ListRecords;

class ListNeeds extends ListRecords
{
    protected static string $resource = NeedResource::class;
}

