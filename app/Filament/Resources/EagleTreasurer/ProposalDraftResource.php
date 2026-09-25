<?php

namespace App\Filament\Resources\EagleTreasurer;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Event;
use App\Models\ProposalDraft;
use App\Services\ProposalHelper;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Read-only list Proposal Draft untuk Eagle Treasurer (bendahara)
 * Sama seperti v2 e-treasurer/proposal-draft/index + detail.
 */
class ProposalDraftResource extends Resource
{
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
                $eventId = session('selected_event') ?? request()->query('event') ?? request()->input('tableFilters.id_event.value') ?? (request()->query('tableFilters')['id_event']['value'] ?? null);
                if (blank($eventId)) return null;
                $event = \App\Models\Event::getCachedEvent($eventId);
                return $event ? ($event->event_name . ' — ' . ($event->institution?->institution_name ?? '')) : null;
            })
            ->description(function () {
                $eventId = session('selected_event') ?? request()->query('event') ?? request()->input('tableFilters.id_event.value') ?? (request()->query('tableFilters')['id_event']['value'] ?? null);
                if (blank($eventId)) return null;
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
            ->filtersLayout(FiltersLayout::AboveContent)->filtersFormColumns(1)->filtersFormWidth('xl')->deferFilters(false)
            ->filters([
                SelectFilter::make('id_event')
                    ->label('Filter Event')
                    ->default(fn () => request()->query('event') ?? request()->input('filters.id_event.value'))
                    ->options(fn () => Event::getCachedSelectOptions()
                    ->mapWithKeys(fn ($e) => [$e->event_defined_id => ProposalHelper::formatDefinedId($e->event_defined_id) . ' | ' . $e->event_name]))
                    ->searchable()->preload()->native(false)
                    ->placeholder('Silakan Pilih Event')
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (filled($value)) {
                            session(['selected_event' => $value]);
                            $query->where('id_event', $value);
                        } else {
                            $eventParam = request()->query('event') ?? request()->input('tableFilters.id_event.value');
                            if (filled($eventParam)) {
                                session(['selected_event' => $eventParam]);
                                $query->where('id_event', $eventParam);
                            } else {
                                session()->forget('selected_event');
                                $query->whereRaw('1=0');
                            }
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
                    ->label(new HtmlString('Deadline <br> Pembayaran'))
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

