<?php

namespace App\Filament\Resources\EagleTreasurer;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\BankAsal;
use App\Models\BankTransfer;
use App\Models\Event;
use App\Models\ProposalDraft;
use App\Services\ProposalHelper;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Read-only list Proposal Draft untuk Eagle Treasurer (bendahara)
 * Sama seperti v2 e-treasurer/proposal-draft/index + detail.
 */
class ProposalDraftResource extends Resource
{
    use HasRoleNavigation;
    use HasRoleNavigation;

    protected static ?string $model = ProposalDraft::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Eagle Treasurer';
    protected static ?string $navigationLabel = 'Proposal Draft';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool { return static::hasRole(Role::EagleTreasurer); }
    public static function canAccess(): bool { return static::hasRole(Role::EagleTreasurer); }

    public static function form(Schema $schema): Schema { return $schema->schema([]); }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['Vendors', 'creativeMember', 'Event.institution']))
            ->heading(function () {
                $eventId = session('selected_event') ?? request()->query('event') ?? request()->input('filters.id_event.value') ?? (request()->query('filters')['id_event']['value'] ?? null);
                if (blank($eventId)) return null;
                if ($eventId === 'ALL') return 'Semua Event';
                $event = \App\Models\Event::getCachedEvent($eventId);
                return $event ? ($event->event_name . ' — ' . ($event->institution?->institution_name ?? '')) : null;
            })
            ->description(function () {
                $eventId = session('selected_event') ?? request()->query('event') ?? request()->input('filters.id_event.value') ?? (request()->query('filters')['id_event']['value'] ?? null);
                if (blank($eventId)) return null;
                if ($eventId === 'ALL') return 'Menampilkan draft dari semua event.';
                $event = \App\Models\Event::getCachedEvent($eventId);
                if (!$event) return null;
                $avail = $event->event_availability ? 'Available' : 'Tidak Available';
                $periode = ($event->event_started_at_formatted ?? $event->event_started_at) . ' s/d ' . ($event->event_finished_at_formatted ?? $event->event_finished_at);
                return "{$periode} | {$avail} | ID: " . \App\Services\ProposalHelper::formatDefinedId($event->event_defined_id) . " | " . $event->event_identity;
            }) 
            // Menghapus link otomatis saat baris tabel diklik
            ->recordUrl(null) 
            // Menghapus aksi otomatis saat baris tabel diklik
            ->recordAction(null) 
            
            ->defaultSort('created_at', 'desc')
            ->filtersLayout(FiltersLayout::AboveContent)->filtersFormColumns(2)->filtersFormWidth('full')->deferFilters(false)
            ->filters([
                SelectFilter::make('id_event')
                    ->label('Filter Event')
                    ->columnSpanFull()
                    ->default(fn () => request()->query('event') ?? request()->input('filters.id_event.value'))
                    ->options(fn () => ['ALL' => 'Semua Event'] + Event::getCachedSelectOptions())
                    ->searchable()->preload()->native(false)
                    ->placeholder('Silakan Pilih Event')
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (filled($value)) {
                            session(['selected_event' => $value]);
                            if ($value !== 'ALL') {
                                $query->where('id_event', $value);
                            }
                        } else {
                            $eventParam = request()->query('event') ?? request()->input('filters.id_event.value');
                            if (filled($eventParam)) {
                                session(['selected_event' => $eventParam]);
                                if ($eventParam !== 'ALL') {
                                    $query->where('id_event', $eventParam);
                                }
                            } else {
                                session()->forget('selected_event');
                                $query->whereRaw('1=0');
                            }
                        }
                    }),
                SelectFilter::make('proposal_draft_status')
                    ->label('Filter Status')
                    ->options([
                        '0' => 'Ditolak',
                        '1' => 'Selesai',
                        '2' => 'Menunggu',
                        '3' => 'Diajukan ke Manager',
                    ])
                    ->native(false)
                    ->placeholder('Semua Status'),
                SelectFilter::make('transfer_status')
                    ->label('Status Transfer')
                    ->options([
                        'need' => 'Perlu Transfer',
                    ])
                    ->native(false)
                    ->placeholder('Semua')
                    ->query(function (Builder $query, array $data) {
                        if (($data['value'] ?? null) === 'need') {
                            $query->whereHas('ProposalSubmissions', fn (Builder $q) => $q->where('proposal_submission_status', 3));
                        }
                    }),
            ])
            ->emptyStateHeading('Silahkan Pilih Event Terlebih Dahulu')
            ->emptyStateDescription('Klik Daftar Draft dari menu Events atau pilih filter Event di atas')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->columns([
                TextColumn::make('#')->rowIndex(),

                TextColumn::make('proposal_draft_defined_id')
                    ->label('Nomor Proposal Draft')
                    ->searchable()
                    ->copyable()
                    ->copyableState(fn ($state) => $state)
                    ->formatStateUsing(fn ($state) => ProposalHelper::formatDefinedId($state)),

                TextColumn::make('created_at')
                    ->label(new HtmlString('Tanggal <br> Pengajuan'))
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('proposal_draft_deadline_payment')
                    ->label(new HtmlString('Tenggat <br> Pembayaran'))
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('proposal_draft_note_member')
                    ->label('Catatan Pengajuan')
                    ->wrap()
                    ->placeholder('-'),

                TextColumn::make('vendors_list')
                    ->label('Vendor')
                    ->html()
                    ->getStateUsing(function ($record) {
                        if ($record->Vendors->isEmpty()) {
                            return '<span class="text-gray-400">-</span>';
                        }

                        return $record->Vendors->map(function ($v) {
                            $nominal = blank($v->vendor_sub_total) ? '-' : e($v->vendor_sub_total);
                            return "
                                <table class='w-full text-xs text-left my-1 border-collapse'>
                                    <tbody>
                                        <tr>
                                            <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap w-1/4'>Nama</td>
                                            <td class='pr-1 py-0.5'>:</td>
                                            <td class='font-medium text-gray-900 dark:text-gray-100 py-0.5'>&nbsp;{$v->vendor_name}</td>
                                        </tr>
                                        <tr>
                                            <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap'>Contact</td>
                                            <td class='pr-1 py-0.5'>:</td>
                                            <td class='text-gray-600 dark:text-gray-400 py-0.5'> &nbsp;{$v->vendor_contact}</td>
                                        </tr>
                                        <tr>
                                            <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap'>Email</td>
                                            <td class='pr-1 py-0.5'>:</td>
                                            <td class='text-gray-600 dark:text-gray-400 py-0.5'> &nbsp;{$v->vendor_email}</td>
                                        </tr>
                                        <tr>
                                            <td class='font-bold text-emerald-600 dark:text-emerald-400 pr-2 py-0.5 whitespace-nowrap'>Nominal</td>
                                            <td class='pr-1 py-0.5 text-emerald-600 dark:text-emerald-400'>:</td>
                                            <td class='font-semibold text-emerald-600 dark:text-emerald-400 py-0.5'>&nbsp;Rp.{$nominal}</td>
                                        </tr>
                                        
                                    </tbody>
                                </table>
                            ";
                        })->implode('<hr class="border-gray-100 dark:border-gray-800 my-1">');
                    }),
                    // ->wrap(),
                    
                TextColumn::make('proposal_draft_status')
                    ->label(new HtmlString('Status <br> Proposal Draft'))
                    ->badge()
                    ->alignCenter()
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

                TextColumn::make('file_attached_name')
                    ->label(new HtmlString('File <br> Pengajuan'))
                    ->formatStateUsing(fn ($state) => blank($state) ? '-' : 'Preview')
                    ->icon(fn ($state) => blank($state) ? null : 'heroicon-o-eye')
                    ->badge(fn ($state) => filled($state))
                    ->color('primary')
                    ->url(fn ($record) => blank($record->file_attached_name) 
                        ? null 
                        : route('proposal.file.download', ['file' => $record->file_attached_name]), 
                        shouldOpenInNewTab: true
                    ),

                TextColumn::make('creativeMember.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->placeholder('-'),
                
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat')->icon('heroicon-o-eye')->color('gray'),
                Action::make('uploadBukti')
                    ->label('Upload Bukti')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->visible(function ($record) {
                        $submission = $record->ProposalSubmissions()->where('proposal_submission_status', 3)->latest()->first();
                        if (! $submission) return false;
                        // Jangan tampilkan jika semua rekening sudah ditransfer
                        $allDone = $submission->BankAccounts->isNotEmpty() && $submission->BankAccounts->every(fn ($ba) => \App\Models\BankTransfer::where('id_bank_account', $ba->id)->exists());
                        return ! $allDone;
                    })
                    ->modalHeading(fn ($record) => 'Upload Bukti TF — ' . ProposalHelper::formatDefinedId($record->proposal_draft_defined_id))
                    ->modalWidth('lg')
                    ->modalSubmitActionLabel('Simpan Bukti')
                    ->schema(function ($record) {
                        $submission = $record->ProposalSubmissions()->where('proposal_submission_status', 3)->latest()->first();
                        if (! $submission) return [];

                        return [
                            Select::make('id_bank_account')
                                ->label('Rekening Tujuan')
                                ->required()
                                ->native(false)
                                ->options(function () use ($submission) {
                                    return $submission->BankAccounts->mapWithKeys(function ($ba) {
                                        $nominal = blank($ba->bank_account_sub_total) ? '-' : 'Rp ' . number_format((int) preg_replace('/\D/', '', (string) $ba->bank_account_sub_total), 0, ',', '.');
                                        $done = $ba->BankTransfer ? ' — Sudah TF' : '';
                                        return [$ba->id => ($ba->Bank?->bank_name ?? '-') . ' · ' . ($ba->bank_account_owner ?? '-') . ' · ' . ($ba->bank_account_number ?? '-') . ' (' . $nominal . ')' . $done];
                                    })->toArray();
                                }),
                            Select::make('id_bank_asal')
                                ->label('Bank Asal (Sumber Dana)')
                                ->required()
                                ->native(false)
                                ->searchable()
                                ->preload()
                                ->options(fn () => BankAsal::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->bank_name . ($b->no_rekening ? ' · ' . $b->no_rekening : '')])->toArray()),
                            TextInput::make('bank_transfer_melalui')
                                ->label('Melalui / Metode')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('cth: BI-FAST, SKN, RTGS, Tunai'),
                            DatePicker::make('bank_transfer_dijalankan_tanggal')
                                ->label('Tanggal Transfer')
                                ->required()
                                ->default(now())
                                ->maxDate(now()),
                            FileUpload::make('transfer_file')
                                ->label('File Bukti Transfer')
                                ->required()
                                ->directory('bukti_tf')
                                ->disk('local')
                                ->maxSize(5120)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->helperText('PDF / JPG / PNG, maks 5 MB.'),
                        ];
                    })
                    ->action(function (array $data, $record) {
                        $submission = $record->ProposalSubmissions()->where('proposal_submission_status', 3)->latest()->first();
                        if (! $submission) {
                            Notification::make()->title('Submission aktif tidak ditemukan.')->danger()->send();
                            return;
                        }

                        $bankAccount = $submission->BankAccounts()->whereKey($data['id_bank_account'])->first();
                        if (! $bankAccount) {
                            Notification::make()->title('Rekening tidak valid.')->danger()->send();
                            return;
                        }

                        $stored = $data['transfer_file'] ?? null;
                        if (is_array($stored)) {
                            $stored = reset($stored);
                        }

                        if (blank($stored)) {
                            Notification::make()->title('File bukti wajib diunggah.')->danger()->send();
                            return;
                        }

                        $fileName = basename(is_string($stored) ? $stored : (string) $stored);

                        DB::transaction(function () use ($data, $record, $submission, $bankAccount, $fileName) {
                            $existing = BankTransfer::where('id_bank_account', $bankAccount->id)->first();

                            if ($existing) {
                                $existing->update([
                                    'transfer_attached_name' => $fileName,
                                    'bank_transfer_melalui' => $data['bank_transfer_melalui'],
                                    'bank_transfer_dijalankan_tanggal' => $data['bank_transfer_dijalankan_tanggal'],
                                    'id_bank_asal' => $data['id_bank_asal'],
                                    'eagle_treasurer' => Auth::id(),
                                ]);
                            } else {
                                $latest = BankTransfer::orderBy('bank_transfer_defined_id', 'desc')->first();
                                $next = ProposalHelper::defined_id(((int) ($latest?->bank_transfer_defined_id ?? 0)) + 1, 3);

                                BankTransfer::create([
                                    'bank_transfer_defined_id' => $next,
                                    'transfer_attached_name' => $fileName,
                                    'bank_transfer_melalui' => $data['bank_transfer_melalui'],
                                    'bank_transfer_dijalankan_tanggal' => $data['bank_transfer_dijalankan_tanggal'],
                                    'id_bank_account' => $bankAccount->id,
                                    'id_bank_asal' => $data['id_bank_asal'],
                                    'eagle_treasurer' => Auth::id(),
                                ]);
                            }

                            $allTransferred = $submission->BankAccounts()->get()->every(
                                fn ($ba) => BankTransfer::where('id_bank_account', $ba->id)->exists()
                            );

                            if ($allTransferred) {
                                $submission->update(['proposal_submission_status' => 1]);
                                $record->update(['proposal_draft_status' => 1]);
                            }
                        });

                        Notification::make()->title('Bukti transfer tersimpan.')->success()->send();
                    }),
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function ($record) {
                        return $record->ProposalSubmissions()
                            ->where('proposal_submission_status', 3)
                            ->exists();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pengajuan')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('proposal_submission_note_manager')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Tuliskan alasan penolakan...'),
                    ])
                    ->action(function (array $data, $record) {
                        $submission = $record->ProposalSubmissions()->where('proposal_submission_status', 3)->latest()->first();
                        if (! $submission) {
                            Notification::make()->title('Submission aktif tidak ditemukan.')->danger()->send();
                            return;
                        }

                        DB::transaction(function () use ($data, $submission, $record) {
                            $submission->update([
                                'proposal_submission_status' => 0,
                                'proposal_submission_note_manager' => $data['proposal_submission_note_manager'],
                            ]);
                            $record->update([
                                'proposal_draft_status' => 0,
                            ]);
                        });

                        Notification::make()->title('Pengajuan berhasil ditolak.')->success()->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\EagleTreasurer\RelationManagers\ProposalSubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\EagleTreasurer\Pages\ListProposalDrafts::route('/'),
            'view' => \App\Filament\Resources\EagleTreasurer\Pages\ViewProposalDraft::route('/{record}'),
        ];
    }
}

