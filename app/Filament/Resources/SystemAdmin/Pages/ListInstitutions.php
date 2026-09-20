<?php

namespace App\Filament\Resources\SystemAdmin\Pages;

use App\Filament\Resources\SystemAdmin\InstitutionResource;
use Filament\Resources\Pages\ListRecords;

class ListInstitutions extends ListRecords
{
    protected static string $resource = InstitutionResource::class;
}

