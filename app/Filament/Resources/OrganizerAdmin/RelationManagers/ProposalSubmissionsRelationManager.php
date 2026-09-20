<?php

namespace App\Filament\Resources\OrganizerAdmin\RelationManagers;

use App\Models\Bank;
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
use App\Services\ProposalHelper;
use Illuminate\Support\Facades\Auth;

class ProposalSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'ProposalSubmissions';
    protected static ?string $title = 'Proposal Submission (Rekening) — Verifikasi';
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
                ])
                ->helperText('Pilih kebutuhan, Fee = khusus'),
            Repeater::make('BankAccounts')
                ->relationship('BankAccounts')
                ->label('Rekening Tujuan')
                ->minItems(1)
                ->schema([
                    Select::make('id_bank')->label('Bank')->options(fn() => Bank::where('bank_active_status', true)->pluck('bank_name', 'bank_defined_id'))->searchable()->required()->native(false),
                    TextInput::make('bank_account_owner')->label('Atas Nama')->required()->maxLength(150)->placeholder('Nama pemilik rekening'),
                    TextInput::make('bank_account_number')->label('No Rekening')->required()->maxLength(20)->placeholder('1234567890'),
                    TextInput::make('bank_account_sub_total')->label('Sub Total (Rp)')->required()->maxLength(50)->placeholder('9000000')->helperText('Ketik 100000 jadi 100.000, tersimpan 100000 di DB')->mask(\Filament\Support\RawJs::make('$money($input, 0, \'.\', \',\')'))->stripCharacters(['.', ','])->dehydrateStateUsing(fn ($s) => blank($s) ? null : preg_replace('/\D/', '', (string) $s))->formatStateUsing(fn ($s) => blank($s) ? null : number_format((int) preg_replace('/\D/', '', (string) $s), 0, ',', '.')),
                ])->columns(2)->collapsible()->itemLabel(fn (array $state): ?string => ($state['bank_account_owner'] ?? null) ? $state['bank_account_owner'] . ' - ' . ($state['bank_account_number'] ?? '') : null)->addActionLabel('Tambah Rekening')->addAction(fn ($action) => $action->label('Tambah Rekening')->icon('heroicon-o-plus')->color('primary')->button())->deleteAction(fn ($action) => $action->requiresConfirmation())->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->striped()
            ->recordAction(null)->columns([
                TextColumn::make('#')->rowIndex(),
                TextColumn::make('proposal_submission_event_identity')->label('Kode')->searchable(),
                TextColumn::make('Needs.need_name')->label('Kebutuhan')->badge()->separator(','),
                TextColumn::make('proposal_submission_status')->label('Status')->badge()->formatStateUsing(fn($s)=>match((string)$s){'0'=>'Tolak','1'=>'Selesai','2'=>'Menunggu','3'=>'Proses TF','4'=>'Dikembalikan',default=>$s})->color(fn($s)=>match((string)$s){'2'=>'warning','3'=>'info','0'=>'danger','1'=>'success','4'=>'gray',default=>'gray'}),
                TextColumn::make('rekening')
                    ->label('Rekening')
                    ->html()
                    ->getStateUsing(function ($record) {
                        if ($record->BankAccounts->isEmpty()) {
                            return '<span class="text-gray-400 text-xs">-</span>';
                        }

                        return $record->BankAccounts->map(function ($ba) {
                            $bankName = e($ba->Bank?->bank_name ?? '-');
                            $owner = e($ba->bank_account_owner ?? '-');
                            $number = e($ba->bank_account_number ?? '-');

                            return "
                                <table class='w-full text-xs text-left my-1 border-collapse'>
                                    <tbody>
                                        <tr>
                                            <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap w-16'>Bank</td>
                                            <td class='pr-1 py-0.5 text-gray-400'>:</td>
                                            <td class='font-medium text-gray-900 dark:text-gray-100 py-0.5'>{$bankName}</td>
                                        </tr>
                                        <tr>
                                            <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap'>Pemilik</td>
                                            <td class='pr-1 py-0.5 text-gray-400'>:</td>
                                            <td class='text-gray-600 dark:text-gray-400 py-0.5'>{$owner}</td>
                                        </tr>
                                        <tr>
                                            <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap'>No. Rek</td>
                                            <td class='pr-1 py-0.5 text-gray-400'>:</td>
                                            <td class='py-0.5 underline'>{$number}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            ";
                        })->implode('<hr class="border-gray-100 dark:border-gray-800 my-1.5">');
                    }),
            ])->headerActions([
                CreateAction::make()
                    ->label('Buat Submission')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->disabled(function () {
                        $draft = $this->getOwnerRecord();
                        $draftStatus = (int) ($draft->proposal_draft_status ?? 2);
                        // v2: block jika sudah ada submission berstatus 1/2/3; boleh buat saat draft status 2/3
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
                        if (! in_array($draftStatus, [2, 3])) return 'Draft harus berstatus Menunggu (2) atau Diajukan (3).';
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
                $newIndex = !$latest ? substr($draftId,12,3).'00' : substr($latest->proposal_submission_index,0,3).ProposalHelper::defined_id(intval(substr($latest->proposal_submission_index,3,2))+1,2);
                $data['proposal_submission_defined_id']=substr($draftId,0,12).$newIndex;
                $data['proposal_submission_index']=$newIndex;
                $data['id_proposal_draft']=$draftId;
                $data['proposal_submission_status']=2;
                $data['organizer_admin']=Auth::id();
                return $data;
            }),
        ])->recordActions([ViewAction::make(), EditAction::make()->visible(fn ($r) => in_array((int)$r->proposal_submission_status, [2,4])), \Filament\Actions\Action::make('ajukanUlang')->label('Ajukan Ulang')->icon('heroicon-o-arrow-path')->color('warning')->visible(fn ($r) => in_array((int)$r->proposal_submission_status, [0]))->requiresConfirmation()->modalHeading('Ajukan Ulang Ditolak')->action(function ($record) {
                $draftId=$record->id_proposal_draft; $prefix15=substr($draftId,0,15); $subs=\App\Models\ProposalSubmission::whereRaw('SUBSTRING(id_proposal_draft,1,15)=?',[$prefix15])->orderBy('proposal_submission_index','desc')->get();
                if(count($subs)>=3){ \Filament\Notifications\Notification::make()->title('Gagal: max 3x')->danger()->send(); return; }
                if(count($subs->where('proposal_submission_status',2))>0){ \Filament\Notifications\Notification::make()->title('Gagal: masih ada Menunggu')->danger()->send(); return; }
                $latest=$subs->first(); $rev=intval(substr($latest->proposal_submission_index,3,2))+1; $newIndex=substr($latest->proposal_submission_index,0,3).\App\Services\ProposalHelper::defined_id($rev,2); $newDefinedId=substr($draftId,0,12).$newIndex;
                $new=\App\Models\ProposalSubmission::create(['proposal_submission_defined_id'=>$newDefinedId,'proposal_submission_index'=>$newIndex,'id_proposal_draft'=>$draftId,'organizer_admin'=>Auth::id()??$record->organizer_admin,'proposal_submission_event_identity'=>$record->proposal_submission_event_identity,'proposal_submission_booking_code'=>$record->proposal_submission_booking_code,'proposal_submission_status'=>2,'proposal_submission_note_manager'=>'not set']);
                foreach($record->BankAccounts as $ba){ \App\Models\BankAccount::create(['id_proposal_submission'=>$newDefinedId,'id_bank'=>$ba->id_bank,'bank_account_owner'=>$ba->bank_account_owner,'bank_account_number'=>$ba->bank_account_number,'bank_account_sub_total'=>$ba->bank_account_sub_total]); }
                foreach($record->Needs as $need){ \Illuminate\Support\Facades\DB::table('need_submissions')->insert(['id_proposal_submission'=>$newDefinedId,'id_need'=>$need->need_defined_id]); }
                \Filament\Notifications\Notification::make()->title('Berhasil: '.$newDefinedId)->success()->send();
            })]);
    }
}
