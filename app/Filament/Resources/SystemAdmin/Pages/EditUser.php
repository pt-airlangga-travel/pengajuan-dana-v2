<?php

namespace App\Filament\Resources\SystemAdmin\Pages;

use App\Filament\Resources\SystemAdmin\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}

