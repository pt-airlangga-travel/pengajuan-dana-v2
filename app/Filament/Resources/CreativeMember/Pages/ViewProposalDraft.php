<?php

namespace App\Filament\Resources\CreativeMember\Pages;

use App\Filament\Resources\CreativeMember\ProposalDraftResource;
use App\Services\ProposalHelper;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Components\Section;

class ViewProposalDraft extends ViewRecord
{
    protected static string $resource = ProposalDraftResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make()
                    ->heading(
                        fn($record) => $record->Event
                            ? "{$record->Event->event_name} — " . ($record->Event->institution?->institution_name ?? '-')
                            : 'Informasi Event'
                    )
                    ->description(function ($record) {
                        $event = $record->Event;
                        if (!$event) return null;

                        $startedAt = $event->event_started_at ? \Carbon\Carbon::parse($event->event_started_at)->format('d M Y') : '-';
                        $finishedAt = $event->event_finished_at ? \Carbon\Carbon::parse($event->event_finished_at)->format('d M Y') : '-';
                        $avail = $event->event_availability ? 'Available' : 'Tidak Available';
                        $formattedId = \App\Services\ProposalHelper::formatDefinedId($event->event_defined_id);

                        return "{$startedAt} s/d {$finishedAt} | {$avail} | ID: {$formattedId} | {$event->event_identity}";
                    })
                    ->icon(\Filament\Support\Icons\Heroicon::CalendarDays)
                    ->columnSpanFull()
                    ->schema([]), // Kosongkan schema jika informasi sudah tercakup di heading/desc

                Section::make()
                    ->heading(fn ($record) => "Proposal Draft #" . ProposalHelper::formatDefinedId($record->proposal_draft_defined_id))
                    ->description(function ($record) {
                        $created = $record->created_at ? $record->created_at->format('d M Y H:i') : '-';
                        $deadline = $record->proposal_draft_deadline_payment ? \Carbon\Carbon::parse($record->proposal_draft_deadline_payment)->format('d M Y') : '-';
                        $author = $record->creativeMember?->name ?? '-';

                        return "Dibuat oleh: {$author} | Tgl: {$created} | Deadline: {$deadline}";
                    })
                    ->icon(Heroicon::DocumentText)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        // 1. Status Proposal
                        TextEntry::make('proposal_draft_status')
                            ->label('Status Proposal Draft')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match((string) $state) {
                                '0'=>'Ditolak',
                                '1'=>'Selesai',
                                '2'=>'Menunggu',
                                '3'=>'Diajukan ke Manager',
                                default => (string) $state,
                            })
                            ->color(fn ($state) => match((string) $state) {
                                '0'=>'danger',
                                '1'=>'success',
                                '2'=>'warning',
                                '3'=>'info',
                                default => 'gray',
                            }),

                        // 2. File Pengajuan (Tombol Native via Badge)
                        TextEntry::make('file_attached_name')
                            ->label('File Pengajuan')
                            ->formatStateUsing(fn ($state) => blank($state) ? '-' : 'Preview File')
                            ->icon(fn ($state) => blank($state) ? null : 'heroicon-o-paper-clip')
                            ->badge(fn ($state) => filled($state))
                            ->color('primary')
                            ->url(fn ($record) => blank($record->file_attached_name) 
                                ? null 
                                : route('proposal.file.download', ['file' => $record->file_attached_name]), 
                                shouldOpenInNewTab: true
                            ),

                        // 3. Catatan Member (Hanya tampil jika ada isinya)
                        TextEntry::make('proposal_draft_note_member')
                            ->label('Catatan Pengajuan (Member)')
                            ->placeholder('-')
                            // ->columnSpanFull()
                            ->visible(fn ($record) => filled($record->proposal_draft_note_member)),

                        // 4. Catatan Admin (Hanya tampil jika ada isinya)
                        TextEntry::make('proposal_draft_note_admin')
                            ->label('Catatan Admin')
                            ->placeholder('-')
                            // ->columnSpanFull()
                            ->visible(fn ($record) => filled($record->proposal_draft_note_admin)),

                        // 5. Vendor List (Native Filament RepeatableEntry dengan layout compact 4-kolom)
                        RepeatableEntry::make('Vendors')
                            ->label('Daftar Vendor')
                            ->schema([
                                TextEntry::make('vendor_name')
                                    ->label('Nama Vendor')
                                    ->weight('bold'),

                                TextEntry::make('vendor_contact')
                                    ->label('Kontak')
                                    ->placeholder('-'),

                                TextEntry::make('vendor_email')
                                    ->label('Email')
                                    ->placeholder('-'),

                                TextEntry::make('vendor_sub_total')
                                    ->label('Nominal')
                                    ->formatStateUsing(fn ($state) => blank($state) ? '-' : 'Rp. ' . number_format((int) preg_replace('/\D/', '', (string) $state), 0, ',', '.'))
                                    ->color('success')
                                    ->weight('semibold')
                                    ->copyable(),
                            ])
                            ->columns(4) // Membagi item vendor menjadi 1 baris ramping
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
