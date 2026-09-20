<?php

namespace App\Filament\Resources\SystemAdmin\Pages;

use App\Filament\Resources\SystemAdmin\BankResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBank extends CreateRecord
{
    protected static string $resource = BankResource::class;
}

