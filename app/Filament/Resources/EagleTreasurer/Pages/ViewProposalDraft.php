<?php

namespace App\Filament\Resources\EagleTreasurer\Pages;

use App\Filament\Resources\EagleTreasurer\ProposalDraftResource;
use App\Services\ProposalHelper;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ViewProposalDraft extends ViewRecord
{
    protected static string $resource = ProposalDraftResource::class;

    public function getRouteKeyName(): string
    {
        return 'proposal_draft_defined_id';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Event')
                ->icon(Heroicon::CalendarDays)->columnSpanFull()->columns(3)->schema([
                    TextEntry::make('Event.event_defined_id')
                        ->label('ID Acara')
                        ->copyable(),
                    TextEntry::make('Event.event_name')->label('Nama Acara')->weight('bold'),
                    TextEntry::make('Event.institution.institution_name')->label('Institusi')->placeholder('-'),
                    TextEntry::make('Event.event_identity')->label('Identitas')->placeholder('-')->copyable()->icon('heroicon-o-link'),
                    TextEntry::make('Event.event_started_at')->label('Mulai')->date('d M Y')->placeholder('-'),
                    TextEntry::make('Event.event_finished_at')->label('Selesai')->date('d M Y')->placeholder('-'),
                ]),
            Section::make('Informasi Proposal Draft')->icon(Heroicon::DocumentText)->columnSpanFull()->columns(3)->schema([
                TextEntry::make('proposal_draft_defined_id')
                    ->label('ID Draft')
                    ->copyable()
                    ->weight('bold'),

                TextEntry::make('proposal_draft_deadline_payment')
                    ->label('Tenggat Waktu')
                    ->date('d M Y')
                    ->placeholder('-'),

                TextEntry::make('file_attached_name')
                    ->label('File Pengajuan')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition(IconPosition::After)
                    ->url(fn ($record) => blank($record?->file_attached_name) ? null : route('proposal.file.download',['file'=>$record->file_attached_name]))
                    ->openUrlInNewTab()
                    ->placeholder('-'),

                TextEntry::make('proposal_draft_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((string) $state) {
                        '0' => 'Ditolak',
                        '1' => 'Selesai',
                        '2' => 'Menunggu',
                        '3' => 'Diajukan ke Manager',
                        default => $state,
                    })
                    ->color(fn ($state) => match ((string) $state) {
                        '0' => 'danger',
                        '1' => 'success',
                        '2' => 'warning',
                        '3' => 'info',
                        default => 'gray',
                    }),
                
                TextEntry::make('proposal_draft_note_member')
                    ->label('Catatan Pengajuan')
                    ->placeholder('-')
                    ->columnSpanFull(),

                RepeatableEntry::make('Vendors')->label('Daftar Vendor')->columns(2)->schema([
                    TextEntry::make('vendor_name')
                        ->label('Nama')
                        ->weight('bold'),
                    TextEntry::make('vendor_sub_total')
                        ->icon('heroicon-o-paper-clip')
                        ->iconPosition(IconPosition::After)
                        ->label('Nominal')
                        ->formatStateUsing(fn ($state) => blank($state) ? '-' : 'Rp. ' . number_format((int) preg_replace('/\D/', '', (string) $state), 0, ',', '.'))
                        ->weight('semibold')
                        ->copyable()
                        ->copyableState(fn ($state) => $state),

                    TextEntry::make('vendor_contact')->label('Kontak')->placeholder('-'),
                    TextEntry::make('vendor_email')->label('Email')->placeholder('-'),
                ])->columnSpanFull(),
            ]),
        ]);
    }
}
