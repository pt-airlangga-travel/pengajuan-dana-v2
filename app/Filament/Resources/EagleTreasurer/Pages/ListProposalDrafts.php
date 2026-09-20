<?php

namespace App\Filament\Resources\EagleTreasurer\Pages;

use App\Filament\Resources\EagleTreasurer\ProposalDraftResource;
use Filament\Resources\Pages\ListRecords;

class ListProposalDrafts extends ListRecords
{
    protected static string $resource = ProposalDraftResource::class;
}
