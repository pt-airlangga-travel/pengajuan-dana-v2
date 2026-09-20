<?php

namespace App\Filament\Resources\InspiringManager\RelationManagers;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProposalSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'ProposalSubmissions';
    protected static ?string $title = 'Approval Submission';

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
            ])->recordActions([
            Action::make('setujui')->label('Setujui')->color('success')->icon('heroicon-o-check')->requiresConfirmation()->action(function($record){
                $record->update(['proposal_submission_status'=>3,'proposal_submission_note_manager'=>'Disetujui','inspiring_manager'=>Auth::id(),'checked_date'=>now()]);
            })->visible(fn($record)=> (int)$record->proposal_submission_status===2),
            Action::make('tolak')->label('Tolak')->color('danger')->icon('heroicon-o-x-mark')->requiresConfirmation()->form([\Filament\Forms\Components\Textarea::make('proposal_submission_note_manager')->label('Alasan')->required()])->action(function($record, array $data){
                $record->update(['proposal_submission_status'=>0,'proposal_submission_note_manager'=>$data['proposal_submission_note_manager'],'inspiring_manager'=>Auth::id(),'checked_date'=>now()]);
            })->visible(fn($record)=> (int)$record->proposal_submission_status===2),
        ]);
    }
}
