<?php

namespace App\Filament\Resources\EagleTreasurer\Pages;

use App\Filament\Resources\EagleTreasurer\ProposalDraftResource;
use App\Services\ProposalHelper;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ViewProposalDraft extends ViewRecord
{
    protected static string $resource = ProposalDraftResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make(fn ($record) => $record->Event ? "{$record->Event->event_name} — " . ($record->Event->institution?->institution_name ?? '-') : 'Informasi Event')
                ->icon(\Filament\Support\Icons\Heroicon::CalendarDays)->columnSpanFull()->columns(3)->schema([
                    TextEntry::make('Event.event_defined_id')->label('ID Event')->formatStateUsing(fn ($s) => ProposalHelper::formatDefinedId($s))->copyable(),
                    TextEntry::make('Event.event_name')->label('Nama Event')->weight('bold'),
                    TextEntry::make('Event.institution.institution_name')->label('Institusi')->placeholder('-'),
                    TextEntry::make('Event.event_started_at')->label('Mulai')->date('d M Y')->placeholder('-'),
                    TextEntry::make('Event.event_finished_at')->label('Selesai')->date('d M Y')->placeholder('-'),
                    TextEntry::make('Event.event_identity')->label('Identitas')->placeholder('-')->copyable()->icon('heroicon-o-link'),
                ]),
            \Filament\Schemas\Components\Section::make('Info Proposal Draft')->icon(\Filament\Support\Icons\Heroicon::DocumentText)->columnSpanFull()->columns(3)->schema([
                TextEntry::make('proposal_draft_defined_id')->label('ID Draft')->formatStateUsing(fn ($s) => ProposalHelper::formatDefinedId($s))->copyable()->weight('bold'),
                TextEntry::make('proposal_draft_status')->label('Status')->badge()->formatStateUsing(fn ($s) => match((string) $s){'0'=>'Ditolak','1'=>'Selesai','2'=>'Menunggu','3'=>'Diajukan ke Manager',default=>$s})->color(fn($s)=>match((string)$s){'0'=>'danger','1'=>'success','2'=>'warning','3'=>'info',default=>'gray'}),
                TextEntry::make('proposal_draft_deadline_payment')->label('Deadline')->date('d M Y')->placeholder('-'),
                TextEntry::make('file_attached_name')->label(new HtmlString('File Pengajuan'))->icon('heroicon-o-paper-clip')->url(fn ($r) => blank($r->file_attached_name)?null:route('proposal.file.download',['file'=>$r->file_attached_name]))->openUrlInNewTab()->copyable()->placeholder('-'),
                TextEntry::make('proposal_draft_note_member')->label('Catatan Pengajuan')->placeholder('-')->columnSpanFull(),
                RepeatableEntry::make('Vendors')->label('Daftar Vendor')->columns(2)->schema([
                    TextEntry::make('vendor_name')->label('Nama')->weight('bold'),
                    TextEntry::make('vendor_sub_total')->label('Nominal')->formatStateUsing(fn ($state) => blank($state) ? '-' : 'Rp. ' . number_format((int) preg_replace('/\D/', '', (string) $state), 0, ',', '.'))->color('success')->weight('semibold')->copyable(),
                    TextEntry::make('vendor_contact')->label('Kontak')->placeholder('-'),
                    TextEntry::make('vendor_email')->label('Email')->placeholder('-'),
                ])->columnSpanFull(),
            ]),
        ]);
    }
}
