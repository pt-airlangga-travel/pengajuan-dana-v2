<?php

namespace App\Filament\Resources\CreativeMember\RelationManagers;

use App\Models\Bank;
use App\Models\Need;
use App\Services\ProposalHelper;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ProposalSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'ProposalSubmissions';
    protected static ?string $title = 'Proposal Submission';
    protected static ?string $modelLabel = 'Submission';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('proposal_submission_booking_code')->label('Booking Code')->maxLength(50)->default('not set'),
            Select::make('needs')
                ->label('Kebutuhan (Needs)')
                ->multiple()
                ->relationship('Needs', 'need_name')
                ->preload()->searchable()
                ->createOptionForm([
                    TextInput::make('need_name')->required()->maxLength(100)->helperText('Auto create need_defined_id 3char'),
                ]),

            Repeater::make('BankAccounts')
                ->relationship('BankAccounts')
                ->label('Rekening Tujuan')
                ->minItems(1)
                ->schema([
                    Select::make('id_bank')
                        ->label('Bank')
                        ->options(fn() => Bank::where('bank_active_status', true)
                        ->pluck('bank_name', 'bank_defined_id'))
                        ->searchable()
                        ->required()
                        ->native(false),

                    TextInput::make('bank_account_owner')
                        ->label('Atas Nama')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('Nama pemilik rekening'),

                    TextInput::make('bank_account_number')
                        ->label('No Rekening')
                        ->required()
                        ->maxLength(20)
                        ->placeholder('1234567890'),

                    TextInput::make('bank_account_sub_total')
                        ->label('Sub Total (Rp)')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('19200000')
                        // $money($input, precision, thousands, decimal)
                        ->mask(\Filament\Support\RawJs::make('$money($input, 0, \'.\', \',\')'))
                        ->stripCharacters(['.', ','])
                        ->dehydrateStateUsing(fn ($s) => blank($s) ? null : preg_replace('/\D/', '', (string) $s))
                        ->formatStateUsing(fn ($s) => blank($s) ? null : number_format((int) preg_replace('/\D/', '', (string) $s), 0, ',', '.')),

                ])->columns(2)->collapsible()->itemLabel(fn (array $state): ?string => ($state['bank_account_owner'] ?? null) ? $state['bank_account_owner'] . ' - ' . ($state['bank_account_number'] ?? '') : null)->addActionLabel('Tambah Rekening')->addAction(fn ($action) => $action->label('Tambah Rekening')->icon('heroicon-o-plus')->color('primary')->button())->deleteAction(fn ($action) => $action->requiresConfirmation())->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->striped()
            ->recordAction(null)
            ->columns([
                TextColumn::make('#')->rowIndex(),
                TextColumn::make('proposal_submission_event_identity')->label('Kode')->searchable(),
                TextColumn::make('Needs.need_name')->label('Kebutuhan')->badge()->separator(','),
                TextColumn::make('rekening')
                    ->label('Rekening Penerima')
                    ->getStateUsing(function ($record) {
                        if ($record->BankAccounts->isEmpty()) {
                            return new HtmlString(
                                '<span class="text-gray-400 text-xs">-</span>'
                            );
                        }

                        return new HtmlString(
                            $record->BankAccounts
                                ->map(function ($ba) {
                                    $bankName = e($ba->Bank?->bank_name ?? '-');
                                    $owner = e($ba->bank_account_owner ?? '-');
                                    $number = e($ba->bank_account_number ?? '-');

                                    $copyNumber = json_encode(
                                        $ba->bank_account_number ?? '-',
                                        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                    );

                                    // Gunakan &quot; untuk string alert di dalam HTML attribute
                                    $jsAlert = "&quot;Nomor rekening berhasil disalin!&quot;";
                                    $onclick = "navigator.clipboard.writeText({$copyNumber}).then(function() { alert({$jsAlert}); })";

                                    return "
                                        <table class='w-full text-xs text-left my-1 border-collapse'>
                                            <tbody>
                                                <tr>
                                                    <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap w-16'>Bank</td>
                                                    <td class='pr-1 py-0.5 text-gray-400'>:</td>
                                                    <td class='font-medium text-gray-900 dark:text-gray-100 py-0.5'>&nbsp;{$bankName}</td>
                                                </tr>
                                                <tr>
                                                    <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap'>Pemilik</td>
                                                    <td class='pr-1 py-0.5 text-gray-400'>:</td>
                                                    <td class='text-gray-600 dark:text-gray-400 py-0.5'>&nbsp;{$owner}</td>
                                                </tr>
                                                <tr>
                                                    <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap'>No. Rek</td>
                                                    <td class='pr-1 py-0.5 text-gray-400'>:</td>
                                                    <td class='py-0.5'>
                                                        <span class='font-mono underline'>&nbsp;{$number}</span>
                                                        <button
                                                            type='button'
                                                            onclick='{$onclick}'
                                                            title='Salin nomor rekening'
                                                            class='ml-1 text-gray-400 hover:text-primary-600 cursor-pointer'
                                                        >
                                                            (⧉)
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    ";
                                })
                                ->implode('<hr class="border-gray-100 dark:border-gray-800 my-1.5">')
                        );
                    }),
                TextColumn::make('proposal_submission_booking_code')
                    ->label('Booking Code'),

                TextColumn::make('proposal_submission_status')
                    ->label('Status Submission')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => [
                        '0' => 'Tolak',
                        '1' => 'Selesai',
                        '2' => 'Menunggu',
                        '3' => 'Proses TF',
                        '4' => 'Dikembalikan',
                    ][$state] ?? $state)
                    ->color(fn ($state) => [
                        '0' => 'danger',
                        '1' => 'success',
                        '2' => 'warning',
                        '3' => 'info',
                        '4' => 'gray',
                    ][$state] ?? 'gray'),
            ])
            ->emptyStateHeading('Belum ada Submission')
            ->emptyStateDescription(function () {
                $draft = $this->getOwnerRecord();
                // Rule v2: boleh buat saat draft status 2/3; block jika sudah ada submission berstatus 1/2/3
                $draftStatus = (int) ($draft->proposal_draft_status ?? 2);
                $activeCount = \App\Models\ProposalSubmission::where('id_proposal_draft', $draft->proposal_draft_defined_id)
                    ->whereIn('proposal_submission_status', [1, 2, 3])
                    ->count();
                if ($activeCount > 0) return 'Sudah ada submission yang sedang berproses/selesai. Tunggu proses atau gunakan Ajukan Ulang jika ditolak (0).';
                if (! in_array($draftStatus, [2, 3])) return 'Draft belum bisa dibuat submission (status harus Menunggu 2 atau Diajukan 3).';
                return 'Klik Buat Submission untuk tambah rekening.';
            })
            ->emptyStateIcon('heroicon-o-banknotes')
            ->headerActions([
                CreateAction::make()
                    ->label('Buat Submission')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->disabled(function () {
                        $draft = $this->getOwnerRecord();
                        $draftStatus = (int) ($draft->proposal_draft_status ?? 2);
                        // v2: block jika sudah ada submission berstatus 1/2/3
                        $activeCount = \App\Models\ProposalSubmission::where('id_proposal_draft', $draft->proposal_draft_defined_id)
                            ->whereIn('proposal_submission_status', [1, 2, 3])
                            ->count();
                        return ! in_array($draftStatus, [2, 3]) || $activeCount > 0;
                    })
                    ->tooltip(function () {
                        $draft = $this->getOwnerRecord();
                        $draftStatus = (int) ($draft->proposal_draft_status ?? 2);
                        $activeCount = \App\Models\ProposalSubmission::where('id_proposal_draft', $draft->proposal_draft_defined_id)
                            ->whereIn('proposal_submission_status', [1, 2, 3])
                            ->count();
                        if (! in_array($draftStatus, [2, 3])) return 'Draft harus berstatus Menunggu (2) atau Diajukan (3) untuk membuat submission.';
                        if ($activeCount > 0) return 'Sudah ada submission berstatus Selesai/Menunggu/Proses TF. Gunakan Ajukan Ulang jika ditolak (0).';
                        return 'Buat submission rekening baru';
                    })
                    ->mutateDataUsing(function (array $data): array {
                        // replicate v2 ProposalSubmissionController@store logic
                        $draft = $this->getOwnerRecord();
                        $draftId = $draft->proposal_draft_defined_id;
                        // cari latest index untuk prefix 15
                        $prefix15 = substr($draftId, 0, 15);
                        // tapi submission defined_id = substr(draft,0,12) + index 5 (seq 3 + rev 2)
                        $latest = \App\Models\ProposalSubmission::whereRaw('SUBSTRING(id_proposal_draft,1,15)=?', [$prefix15])->orderBy('proposal_submission_index','desc')->first();
                        if (!$latest) {
                            $seq = substr($draftId, 12, 3);
                            $newIndex = $seq . '00';
                        } else {
                            $seq = substr($latest->proposal_submission_index, 0, 3);
                            $rev = intval(substr($latest->proposal_submission_index, 3, 2)) + 1;
                            // max 3
                            if (\App\Models\ProposalSubmission::whereRaw('SUBSTRING(id_proposal_draft,1,15)=?', [$prefix15])->count() >= 3) {
                                \Filament\Notifications\Notification::make()->title('Max 3 submission per draft')->danger()->send();
                                return $data;
                            }
                            $newIndex = $seq . ProposalHelper::defined_id($rev, 2);
                        }
                        $definedId = substr($draftId, 0, 12) . $newIndex;
                        // event_identity: year/institution/short_name/month/date/seq3
                        $event = $draft->Event;
                        $institution = $event?->id_institution ?? '000';
                        $short = $event?->event_short_name ?? 'EVT';
                        $month = $event?->event_month ?? date('M');
                        $date = $event?->event_date ?? date('d');
                        $year = $event?->event_year ?? date('Y');
                        $data['proposal_submission_defined_id'] = $definedId;
                        $data['proposal_submission_index'] = $newIndex;
                        $data['proposal_submission_event_identity'] = $year . '/' . $institution . '/' . $short . '/' . $month . '/' . $date . '/' . substr($newIndex, 0, 3);
                        $data['id_proposal_draft'] = $draftId;
                        $data['proposal_submission_status'] = 2;
                        $data['organizer_admin'] = Auth::id() ?? 2;
                        $data['proposal_submission_note_manager'] = 'not set';
                        $data['proposal_submission_booking_code'] = $data['proposal_submission_booking_code'] ?? 'not set';
                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => in_array((int)$record->proposal_submission_status, [2,4])),
                \Filament\Actions\Action::make('cetakFormulir')
                    ->label('Cetak Formulir')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn ($record) => route('print.formulir', ['id' => $record->proposal_submission_defined_id]), shouldOpenInNewTab: true)
                    ->visible(fn ($record) => (int)$record->proposal_submission_status === 1),
                \Filament\Actions\Action::make('lihatBuktiTF')
                    ->label('Preview Bukti TF')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->visible(fn ($record) => $record->BankAccounts->contains(fn ($ba) => \App\Models\BankTransfer::where('id_bank_account', $ba->id)->exists()))
                    ->schema(function ($record) {
                        $items = $record->BankAccounts->flatMap(function ($ba) {
                            return \App\Models\BankTransfer::where('id_bank_account', $ba->id)->get()->map(function ($bt) use ($ba) {
                                return [
                                    'bank' => $ba->Bank?->bank_name ?? '-',
                                    'owner' => $ba->bank_account_owner,
                                    'number' => $ba->bank_account_number,
                                    'file' => basename($bt->transfer_attached_name),
                                    'url' => route('bukti_tf.download', ['file' => basename($bt->transfer_attached_name)]),
                                ];
                            });
                        });
                        if ($items->isEmpty()) {
                            return [ \Filament\Infolists\Components\TextEntry::make('empty')->label('Info')->state('Tidak ada bukti transfer') ];
                        }
                        // flatMap items sudah flat (array data), bangun array komponen flat
                        $components = [];
                        foreach ($items as $i) {
                            $components[] = \Filament\Infolists\Components\TextEntry::make('bank_' . count($components))->label('Bank')->state($i['bank']);
                            $components[] = \Filament\Infolists\Components\TextEntry::make('owner_' . count($components))->label('Pemilik')->state($i['owner']);
                            $components[] = \Filament\Infolists\Components\TextEntry::make('number_' . count($components))->label('No Rekening')->state($i['number'])->copyable();
                            $components[] = \Filament\Infolists\Components\TextEntry::make('file_' . count($components))->label('File Bukti')
                                ->state($i['file'])
                                ->url($i['url'])
                                ->icon('heroicon-o-paper-clip')
                                ->color('primary');
                        }
                        return $components;
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                \Filament\Actions\Action::make('ajukanUlang')
                    ->label('Ajukan Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => in_array((int)$record->proposal_submission_status, [0]))
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Ulang Submission Ditolak')
                    ->modalDescription('Hanya untuk Ditolak (0). Increment rev 2 digit, max 3x per draft, cek pending 2.')
                    ->action(function ($record) {
                        $draftId = $record->id_proposal_draft;
                        $prefix15 = substr($draftId, 0, 15);
                        $subs = \App\Models\ProposalSubmission::whereRaw('SUBSTRING(id_proposal_draft,1,15)=?', [$prefix15])->orderBy('proposal_submission_index','desc')->get();
                        if (count($subs) >= 3) { \Filament\Notifications\Notification::make()->title('Gagal: max 3x revisi')->danger()->send(); return; }
                        if (count($subs->where('proposal_submission_status', 2)) > 0) { \Filament\Notifications\Notification::make()->title('Gagal: masih ada Menunggu')->danger()->send(); return; }
                        $latest = $subs->first();
                        $rev = intval(substr($latest->proposal_submission_index, 3, 2)) + 1;
                        $newIndex = substr($latest->proposal_submission_index, 0, 3) . \App\Services\ProposalHelper::defined_id($rev, 2);
                        $newDefinedId = substr($draftId, 0, 12) . $newIndex;
                        $event = \App\Models\Event::where('event_defined_id', substr($draftId, 0, 12))->first();
                        $identity = $event ? $event->event_year . '/' . \App\Services\ProposalHelper::defined_id($event->institution->institution_defined_id ?? $event->id_institution, 3) . '/' . $event->event_short_name . '/' . $event->event_month . '/' . $event->event_date . '/' . substr($newIndex, 0, 3) : $record->proposal_submission_event_identity;
                        $new = \App\Models\ProposalSubmission::create(['proposal_submission_defined_id'=>$newDefinedId,'proposal_submission_index'=>$newIndex,'id_proposal_draft'=>$draftId,'organizer_admin'=>\Illuminate\Support\Facades\Auth::id() ?? $record->organizer_admin,'proposal_submission_event_identity'=>$identity,'proposal_submission_booking_code'=>$record->proposal_submission_booking_code,'proposal_submission_status'=>2,'proposal_submission_note_manager'=>'not set']);
                        foreach ($record->BankAccounts as $ba) { \App\Models\BankAccount::create(['id_proposal_submission'=>$newDefinedId,'id_bank'=>$ba->id_bank,'bank_account_owner'=>$ba->bank_account_owner,'bank_account_number'=>$ba->bank_account_number,'bank_account_sub_total'=>$ba->bank_account_sub_total]); }
                        foreach ($record->Needs as $need) { \Illuminate\Support\Facades\DB::table('need_submissions')->insert(['id_proposal_submission'=>$newDefinedId,'id_need'=>$need->need_defined_id]); }
                        \Filament\Notifications\Notification::make()->title('Berhasil: '.$newDefinedId)->success()->send();
                    }),
            ]);
    }
}
