<?php

namespace App\Filament\Resources\EagleTreasurer;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\BankAccount;
use App\Models\BankAsal;
use App\Models\BankTransfer;
use App\Models\ProposalDraft;
use App\Models\ProposalSubmission;
use App\Services\ProposalHelper;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Opsi B: folder EagleTreasurer - Bank Detail per rekening
 * Resource untuk bank_accounts left join bank_transfers (view per id_proposal_submission)
 * Mirip e-treasurer BankController@index + rejectBank + uploadBuktiTransfer
 * - table: bank_name, owner, number, sub_total, status_revised badge, alasan_revised
 * - actions per rekening: Reject (alasan_revised, status_revised=true) dan Upload Bukti Transfer
 *   (FileUpload bukti_tf/{time}.ext, Select id_bank_asal dari BankAsal, bank_transfer_melalui,
 *    generate bank_transfer_defined_id=defined_id(latestId+1,3), eagle_treasurer=Auth::id(),
 *    cek allTransferred -> update submission 1 & draft 1 Selesai transaction)
 */
class BankDetailResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = BankAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Eagle Treasurer';

    protected static ?string $navigationLabel = 'Bank Detail';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool { return static::hasRole(Role::EagleTreasurer); }

    public static function canAccess(): bool { return static::hasRole(Role::EagleTreasurer); }

    public static function form(Schema $schema): Schema { return $schema->schema([]); }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('BankTransfer');
                // left join bank_transfers view: eager load BankTransfer via hasOne? BankAccount has no relation, so via subquery
                // Filter by ?submission=xxx if present in request (dari ProposalSubmissionResource link)
                $submission = request()->query('submission') ?? request()->query('id_proposal_submission');
                if ($submission) {
                    $query->where('id_proposal_submission', $submission);
                }
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('id_proposal_submission')
                    ->label('Submission ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('Bank.bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('bank_account_owner')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('bank_account_number')
                    ->label('Number')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('bank_account_sub_total')
                    ->label('Sub Total')
                    ->sortable(),
                TextColumn::make('status_revised')
                    ->label('Status Revised')
                    ->badge()
                    ->formatStateUsing(fn ($s) => $s ? 'Revised' : 'OK')
                    ->color(fn ($s) => $s ? 'danger' : 'success'),
                TextColumn::make('alasan_revised')
                    ->label('Alasan Revised')
                    ->placeholder('-')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('bankAccountRevised')
                    ->label('Revised')
                    ->getStateUsing(fn ($record) => $record->status_revised ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn ($state) => $state === 'Ya' ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transferStatus')
                    ->label('Bukti TF')
                    ->getStateUsing(fn ($record) => $record->BankTransfer ? 'Uploaded' : 'Belum')
                    ->badge()
                    ->color(fn ($state) => $state === 'Uploaded' ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->schema([
                        Textarea::make('alasan_revised')
                            ->label('Alasan Revised')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000)
                            ->placeholder('Wajib isi alasan reject per rekening'),
                    ])
                    ->action(function (array $data, $record) {
                        // port dari BankController@rejectBank: update bank_accounts status_revised=true, alasan_revised
                        BankAccount::where('id', $record->id)->update([
                            'status_revised' => true,
                            'alasan_revised' => $data['alasan_revised'],
                        ]);
                        \Filament\Notifications\Notification::make()->title('Rekening direject, alasan disimpan')->danger()->send();
                    }),
                Action::make('uploadBukti')
                    ->label('Upload Bukti')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->schema([
                        FileUpload::make('transfer_attached_name')
                            ->label('Bukti Transfer (jpg/png/pdf max 5MB)')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(5120)
                            ->directory('bukti_tf')
                            ->preserveFilenames(false)
                            ->getUploadedFileNameForStorageUsing(fn ($file): string => time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension())
                            ->required()
                            ->helperText('Disimpan ke storage/app/private/bukti_tf/{time}.ext'),
                        Select::make('id_bank_asal')
                            ->label('Bank Asal')
                            ->options(fn () => BankAsal::pluck('bank_name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText('Pilih dari BankAsal'),
                        TextInput::make('bank_transfer_melalui')
                            ->label('Melalui')
                            ->placeholder('contoh: Transfer Bank / Kliring')
                            ->maxLength(255)
                            ->required()
                            ->default('Transfer Bank'),
                    ])
                    ->action(function (array $data, $record) {
                        // port dari BankController@uploadBuktiTransfer + EagleTreasurer confirm allTransferred
                        DB::transaction(function () use ($data, $record) {
                            $latestId = BankTransfer::latest('id')->value('id') ?? 0;
                            $definedId = ProposalHelper::defined_id($latestId + 1, 3);
                            // FileUpload sudah store file ke directory bukti_tf; ambil basename jika path lengkap
                            $file = $data['transfer_attached_name'] ?? null;
                            if (is_array($file)) {
                                $file = reset($file);
                            }
                            // FileUpload kadang return string path relatif seperti bukti_tf/123.pdf
                            $fileName = $file ? basename($file) : (time() . '.pdf');
                            // Jika file masih full path, pastikan relative
                            $transferPath = $file ? $file : ('bukti_tf/' . $fileName);
                            // Simpan jika belum ada (FileUpload sudah handle storage)
                            BankTransfer::create([
                                'bank_transfer_defined_id' => $definedId,
                                'transfer_attached_name' => $transferPath,
                                'bank_transfer_melalui' => $data['bank_transfer_melalui'] ?? 'Transfer Bank',
                                'bank_transfer_dijalankan_tanggal' => now()->toDateString(),
                                'id_bank_account' => $record->id,
                                'id_bank_asal' => $data['id_bank_asal'],
                                'eagle_treasurer' => Auth::id(),
                            ]);

                            // cek allTransferred: foreach bank_accounts has transfer?
                            $submissionId = $record->id_proposal_submission;
                            $accounts = BankAccount::where('id_proposal_submission', $submissionId)->get();
                            $allTransferred = true;
                            foreach ($accounts as $acc) {
                                $has = BankTransfer::where('id_bank_account', $acc->id)->exists();
                                if (! $has) {
                                    $allTransferred = false;
                                    break;
                                }
                            }
                            if ($allTransferred) {
                                ProposalSubmission::where('proposal_submission_defined_id', $submissionId)
                                    ->update(['proposal_submission_status' => 1]);
                                $submission = ProposalSubmission::where('proposal_submission_defined_id', $submissionId)->first();
                                if ($submission && $submission->id_proposal_draft) {
                                    ProposalDraft::where('proposal_draft_defined_id', $submission->id_proposal_draft)
                                        ->update(['proposal_draft_status' => 1]);
                                }
                            }
                        });
                        // notif cek apakah all transferred
                        $submissionId = $record->id_proposal_submission;
                        $accounts = BankAccount::where('id_proposal_submission', $submissionId)->get();
                        $allDone = $accounts->every(fn ($acc) => BankTransfer::where('id_bank_account', $acc->id)->exists());
                        if ($allDone) {
                            \Filament\Notifications\Notification::make()->title('Bukti uploaded & semua rekening transferred -> Submission & Draft Selesai (1)')->success()->send();
                        } else {
                            \Filament\Notifications\Notification::make()->title('Bukti transfer uploaded')->success()->send();
                        }
                    }),
                Action::make('viewBukti')
                    ->label('Lihat Bukti')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn ($record) => BankTransfer::where('id_bank_account', $record->id)->exists())
                    ->schema(function ($record) {
                        $bt = BankTransfer::where('id_bank_account', $record->id)->latest('id')->first();
                        if (! $bt) {
                            return [ \Filament\Infolists\Components\TextEntry::make('empty')->label('Info')->state('Tidak ada bukti') ];
                        }
                        return [
                            \Filament\Infolists\Components\TextEntry::make('bank_transfer_defined_id')->label('Transfer ID')->state($bt->bank_transfer_defined_id),
                            \Filament\Infolists\Components\TextEntry::make('transfer_attached_name')->label('File')
                                ->state(fn () => basename($bt->transfer_attached_name))
                                ->url(fn () => route('bukti_tf.download', ['file' => basename($bt->transfer_attached_name)]))
                                ->icon('heroicon-o-paper-clip')
                                ->color('primary'),
                            \Filament\Infolists\Components\TextEntry::make('bank_transfer_melalui')->label('Melalui')->state($bt->bank_transfer_melalui),
                            \Filament\Infolists\Components\TextEntry::make('id_bank_asal')->label('Bank Asal ID')->state($bt->id_bank_asal),
                            \Filament\Infolists\Components\TextEntry::make('eagle_treasurer')->label('Treasurer ID')->state($bt->eagle_treasurer),
                            \Filament\Infolists\Components\TextEntry::make('bank_transfer_dijalankan_tanggal')->label('Tanggal')->state($bt->bank_transfer_dijalankan_tanggal),
                        ];
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ]);
    }

                public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\EagleTreasurer\Pages\ListBankDetails::route('/'),
            'create' => \App\Filament\Resources\EagleTreasurer\Pages\CreateBankDetail::route('/create'),
            'edit' => \App\Filament\Resources\EagleTreasurer\Pages\EditBankDetail::route('/{record}/edit'),
        ];
    }
}

