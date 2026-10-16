<?php

namespace App\Filament\Resources\EagleTreasurer\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ProposalSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'ProposalSubmissions';
    protected static ?string $title = 'Transfer — Bank Accounts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table->striped()
            ->recordAction(null)->columns([
                TextColumn::make('#')->rowIndex(),
                TextColumn::make('proposal_submission_event_identity')
                    ->label('Kode')
                    ->searchable(),

                TextColumn::make('Needs.need_name')
                    ->label('Kebutuhan')
                    ->badge()
                    ->separator(','),

                // TextColumn::make('proposal_submission_status')->label('Status')->badge()->formatStateUsing(fn($s)=>match((string)$s){'0'=>'Tolak','1'=>'Selesai','2'=>'Menunggu','3'=>'Proses TF','4'=>'Dikembalikan',default=>$s})->color(fn($s)=>match((string)$s){'2'=>'warning','3'=>'info','0'=>'danger','1'=>'success','4'=>'gray',default=>'gray'}),
                TextColumn::make('proposal_submission_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((string) $state) {
                        '0' => 'Ditolak',
                        '1' => 'Selesai',
                        '2' => 'Menunggu',
                        '3' => 'Proses TF',
                        default => $state,
                    })
                    ->color(fn ($state) => match ((string) $state) {
                        '0' => 'danger',
                        '1' => 'success',
                        '2' => 'warning',
                        '3' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('rekening')
                    ->label('Rekening Penerima')
                    ->getStateUsing(function ($record) {
                        if ($record->BankAccounts->isEmpty()) {
                            return new HtmlString('<span class="text-xs text-gray-400">-</span>');
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

                                    $badge = $ba->BankTransfer
                                        ? " <span class='rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30'>Sudah TF</span>"
                                        : " <span class='rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30'>Belum TF</span>";

                                    return "
                                        <table class='w-full text-xs text-left my-1 border-collapse'>
                                            <tbody>
                                                <tr>
                                                    <td class='font-bold text-gray-700 dark:text-gray-300 pr-2 py-0.5 whitespace-nowrap w-16'>Bank</td>
                                                    <td class='pr-1 py-0.5 text-gray-400'>:</td>
                                                    <td class='font-medium text-gray-900 dark:text-gray-100 py-0.5'>&nbsp;{$bankName}{$badge}</td>
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
                                                            (🗐)
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

            ])
            ->recordActions([
                Action::make('detailBank')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Detail Transfer — ' . $record->proposal_submission_event_identity)
                    ->modalDescription(fn ($record) => 'Submission ' . $record->proposal_submission_defined_id)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('5xl')
                    ->modalContent(function ($record) {
                        $record->loadMissing([
                            'BankAccounts.Bank',
                            'BankAccounts.BankTransfer.BankAsal',
                            'BankAccounts.BankTransfer.eagleTreasurer',
                        ]);

                        return view('filament.resources.eagle-treasurer.bank-detail-modal', ['record' => $record]);
                    }),
                Action::make('lihatBukti')
                    ->label('Bukti TF')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->visible(fn ($record) => $record->BankAccounts->contains(fn ($ba) => filled($ba->BankTransfer?->transfer_attached_name)))
                    ->modalHeading(fn ($record) => 'Bukti Transfer — ' . $record->proposal_submission_event_identity)
                    ->modalDescription(fn ($record) => 'Submission ' . $record->proposal_submission_defined_id)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('4xl')
                    ->modalContent(function ($record) {
                        $record->loadMissing([
                            'BankAccounts.Bank',
                            'BankAccounts.BankTransfer.BankAsal',
                            'BankAccounts.BankTransfer.eagleTreasurer',
                        ]);

                        return view('filament.resources.eagle-treasurer.bukti-tf-modal', ['record' => $record]);
                    }),
            ]);
    }
}
