<?php

namespace App\Filament\Resources\EagleTreasurer\Pages;

use App\Filament\Resources\EagleTreasurer\BankDetailResource;
use Filament\Resources\Pages\ListRecords;

class ListBankDetails extends ListRecords
{
    protected static string $resource = BankDetailResource::class;
}

