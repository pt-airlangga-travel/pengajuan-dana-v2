<?php

namespace App\Filament\Resources\EagleTreasurer;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\ProposalSubmission;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

/**
 * Opsi B: folder EagleTreasurer - Eksekusi transfer (LEGACY)
 * Telah diganti oleh ProposalSubmissionResource.php (status 3 Proses TF).
 * File ini dipertahankan agar tidak break, tapi navigasi dinonaktifkan.
 * Gunakan EagleTreasurer\ProposalSubmissionResource untuk proses transfer
 * dan BankDetailResource untuk per-rekening reject/upload bukti.
 */
class BankTransferResource extends Resource
{
    use HasRoleNavigation;
    protected static ?string $model = ProposalSubmission::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null $navigationGroup = 'Eagle Treasurer';
    protected static ?string $navigationLabel = 'Proses Transfer (Legacy)';
    protected static ?int $navigationSort = 99;

    public static function shouldRegisterNavigation(): bool { return false; }
    public static function canAccess(): bool { return static::hasRole(Role::EagleTreasurer); }

    public static function form(Schema $schema): Schema { return $schema->schema([]); }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('proposal_submission_defined_id')->label('Submission ID')->searchable(),
            TextColumn::make('proposal_submission_status')->label('Status')->badge()->formatStateUsing(fn($s)=> $s==3?'Proses TF':$s)->color(fn($s)=> $s==3?'warning':'gray'),
            TextColumn::make('ProposalDraft.proposal_draft_defined_id')->label('Draft ID'),
        ])->recordActions([]);
    }

                public static function getPages(): array
    {
        return [];
    }
}

