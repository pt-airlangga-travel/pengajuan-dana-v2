<?php

namespace App\Filament\Resources\SystemAdmin\Pages;

use App\Filament\Resources\SystemAdmin\NeedResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNeed extends CreateRecord
{
    protected static string $resource = NeedResource::class;
}

