<?php

namespace App\Filament\Resources\CreativeMember;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Event;
use App\Models\ProposalDraft;
use App\Models\Vendor;
use App\Services\ProposalHelper;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ProposalDraftResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = ProposalDraft::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Creative Member';

    protected static ?string $navigationLabel = 'Proposal Draft';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAnyRole([Role::CreativeMember, Role::OrganizerAdmin]);
    }

    public static function canAccess(): bool
    {
        return static::hasAnyRole([Role::CreativeMember, Role::OrganizerAdmin]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Info Pengaju')
                ->columns(3)
                ->schema([
                    TextInput::make('pengaju_name')->label('Nama')->default(fn () => Auth::user()?->name)->disabled()->dehydrated(false),
                    TextInput::make('pengaju_email')->label('Email')->default(fn () => Auth::user()?->email)->disabled()->dehydrated(false),
                    TextInput::make('pengaju_division')->label('Divisi')->default(fn () => Auth::user()?->division?->division_name ?? Auth::user()?->id_division)->disabled()->dehydrated(false),
                ])->columnSpanFull(),
            Section::make('Info Proposal Draft')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('id_event')
                        ->label('Event')
                        ->options(fn () => Event::where('event_availability', true)->orderByDesc('created_at')->get()->mapWithKeys(fn ($e) => [$e->event_defined_id => ProposalHelper::formatDefinedId($e->event_defined_id) . ' | ' . $e->event_name]))
                        ->searchable()->preload()->required()->native(false)->columnSpanFull()
                        ->default(fn () => request()->query('event') ?? request()->input('tableFilters.id_event.value'))
                        ->disabled(fn () => filled(request()->query('event')))
                        ->dehydrated()->live(),

                    \Filament\Schemas\Components\Section::make()
                        ->heading(fn ($get) => ($eid = $get('id_event') ?? request()->query('event')) ? (Event::with('institution')->where('event_defined_id', $eid)->first()?->event_name . ' — ' . (Event::with('institution')->where('event_defined_id', $eid)->first()?->institution?->institution_name ?? '-') ?? 'Informasi Event') : 'Informasi Event')
                        ->description(function ($get) {
                            $eid = $get('id_event') ?? request()->query('event');
                            if (blank($eid)) return 'Pilih Event di atas untuk melihat detail';
                            $event = Event::where('event_defined_id', $eid)->first();
                            if (!$event) return null;
                            $startedAt = $event->event_started_at ? \Carbon\Carbon::parse($event->event_started_at)->format('d M Y') : '-';
                            $finishedAt = $event->event_finished_at ? \Carbon\Carbon::parse($event->event_finished_at)->format('d M Y') : '-';
                            $avail = $event->event_availability ? 'Available' : 'Tidak Available';
                            $formattedId = ProposalHelper::formatDefinedId($event->event_defined_id);
                            return "{$startedAt} s/d {$finishedAt} | {$avail} | ID: {$formattedId} | {$event->event_identity}";
                        })
                        ->compact()
                        ->columnSpanFull()
                        ->visible(fn ($get) => filled($get('id_event') ?? request()->query('event')))
                        ->schema([]),
                    
                    Textarea::make('proposal_draft_note_member')
                        ->label('Note')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    DatePicker::make('proposal_draft_deadline_payment')
                        ->label('Deadline Pembayaran')
                        ->required()
                        ->native(false),

                    Radio::make('jenis_pengajuan')
                        ->label('Jenis Pengajuan')
                        ->options(['fee' => 'Fee', 'non-fee' => 'Non-Fee'])
                        ->default('fee')
                        ->inline()
                        ->live()
                        ->dehydrated(false),

                    Repeater::make('vendors')
                        ->label('Vendors')
                        ->live()
                        ->minItems(1)
                        ->maxItems(fn ($get) => $get('jenis_pengajuan') === 'non-fee' ? 1 : 10)
                        ->addable(fn ($get) => ($get('jenis_pengajuan') ?? 'fee') !== 'non-fee' || count($get('vendors') ?? []) < 1)
                        ->deletable(fn ($get) => ($get('jenis_pengajuan') ?? 'fee') !== 'non-fee' || count($get('vendors') ?? []) > 1)
                        ->schema([
                            TextInput::make('vendor_name')
                                ->label('Nama Vendor')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('vendor_sub_total')
                                ->label('Sub Total (Rp)')
                                ->required()
                                ->maxLength(50)
                                ->mask(\Filament\Support\RawJs::make('$money($input, 0, \'.\', \',\')'))
                                ->stripCharacters(['.', ','])
                                ->dehydrateStateUsing(fn ($state) => blank($state) ? null : preg_replace('/\D/', '', (string) $state))
                                ->formatStateUsing(fn ($state) => blank($state) ? null : number_format((int) preg_replace('/\D/', '', (string) $state), 0, ',', '.')),
                            TextInput::make('vendor_contact')
                                ->label('Kontak')
                                ->maxLength(50),
                            TextInput::make('vendor_email')
                                ->label('Email')
                                ->email()
                                ->maxLength(100),
                        ])
                        ->columns(2)
                        ->addActionLabel(fn ($get) => ($get('jenis_pengajuan') ?? 'fee') === 'non-fee' ? 'Vendor (maks 1 untuk Non-Fee)' : 'Tambah Vendor')
                        ->columnSpanFull(),

                    \Filament\Forms\Components\FileUpload::make('file_attached_name')
                        ->label('File Invoice (pdf/jpg/png, max 5MB)')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('proposal')
                        ->visibility('public')
                        ->preserveFilenames(false)
                        ->required()
                        ->columnSpanFull(),
                ])

        ]);
    }

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
                        ->options(fn () => Event::getCachedSelectOptions())
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
                ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('gray'),

                Action::make('reCreate')
                    ->label('Ajukan Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Ulang Draft')
                    ->modalDescription('Hanya untuk status Ditolak (0). Revisi increment rev 2 digit, max 3x per 15char prefix, cek pending 2.')
                    ->schema([
                        FileUpload::make('file_attached_name')
                            ->label('File Baru (opsional, pdf/jpg/png max 5MB)')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(5120)
                            ->directory('proposal'),
                        DatePicker::make('proposal_draft_deadline_payment')
                            ->label('Deadline Baru')
                            ->required()
                            ->native(false),
                        Textarea::make('proposal_draft_note_member')
                            ->label('Catatan Member')
                            ->rows(3),
                    ])
                    ->action(function (array $data, $record) {
                        // copy logic v2 ProposalDraftController@reCreate + reStore
                        $prefix15 = substr($record->proposal_draft_defined_id, 0, 15);
                        $candidates = ProposalDraft::whereRaw('SUBSTRING(proposal_draft_defined_id,1,15) = ?', [$prefix15])->get();
                        if (count($candidates) >= 3) {
                            \Filament\Notifications\Notification::make()->title('Gagal: max 3 pengajuan per 15char prefix')->danger()->send();
                            return;
                        }
                        if (count($candidates->where('proposal_draft_status', 2)) > 0) {
                            \Filament\Notifications\Notification::make()->title('Gagal: masih ada pengajuan status Menunggu (2)')->danger()->send();
                            return;
                        }
                        $latest = ProposalDraft::whereRaw('SUBSTRING(proposal_draft_defined_id,1,15) = ?', [$prefix15])
                            ->orderBy('proposal_draft_index', 'desc')
                            ->first();
                        $rev = intval(substr($latest->proposal_draft_defined_id, 15, 2)) + 1;
                        $newDefinedId = $prefix15 . ProposalHelper::defined_id($rev, 2);
                        $newIndex = substr($newDefinedId, 12, 5);
                        $fileName = $latest->file_attached_name;
                        if (! empty($data['file_attached_name'])) {
                            // FileUpload menyimpan dengan path relatif; ambil basename
                            $uploaded = $data['file_attached_name'];
                            // jika array (multiple) ambil first
                            if (is_array($uploaded)) {
                                $uploaded = reset($uploaded);
                            }
                            // FileUpload sudah store ke storage/app/private/proposal; kita rename ke {newDefinedId}.ext
                            $ext = pathinfo($uploaded, PATHINFO_EXTENSION) ?: 'pdf';
                            $newFileName = $newDefinedId . '.' . $ext;
                            // coba move file
                            $oldPath = 'proposal/' . basename($uploaded);
                            $newPath = 'proposal/' . $newFileName;
                            if (Storage::disk('local')->exists($oldPath)) {
                                Storage::disk('local')->move($oldPath, $newPath);
                                $fileName = $newFileName;
                            } else {
                                $fileName = $newFileName;
                            }
                        }
                        $new = ProposalDraft::create([
                            'proposal_draft_defined_id' => $newDefinedId,
                            'proposal_draft_index' => $newIndex,
                            'id_event' => $record->id_event,
                            'creative_member' => Auth::id() ?? $record->creative_member,
                            'proposal_draft_deadline_payment' => $data['proposal_draft_deadline_payment'] ?? $record->proposal_draft_deadline_payment,
                            'proposal_draft_note_member' => $data['proposal_draft_note_member'] ?? $record->proposal_draft_note_member,
                            'proposal_draft_note_admin' => 'not set',
                            'proposal_draft_status' => 2,
                            'file_attached_name' => $fileName,
                        ]);
                        // copy vendors dari draft terbaru
                        foreach ($record->Vendors as $v) {
                            Vendor::create([
                                'id_proposal_draft' => $newDefinedId,
                                'vendor_name' => $v->vendor_name,
                                'vendor_contact' => $v->vendor_contact,
                                'vendor_email' => $v->vendor_email,
                                'vendor_sub_total' => $v->vendor_sub_total,
                            ]);
                        }
                        \Filament\Notifications\Notification::make()->title('Berhasil ajukan ulang: ' . $newDefinedId)->success()->send();
                    })
                    ->visible(fn ($record) => 
                        (int) $record->proposal_draft_status === 0 
                        && static::hasAnyRole([Role::CreativeMember, Role::OrganizerAdmin])
                    )
            ]);
    }

    /**
     * Logic create mirip v2 ProposalDraftController@store
     * Dipanggil dari Create Page mutateDataBeforeCreate jika Page diaktifkan.
     */
    public static function handleStore(array $validatedData, $uploadedFile = null): ProposalDraft
    {
        // $validatedData harus mengandung id_event, proposal_draft_deadline_payment, proposal_draft_note_member optional
        $latestIndex = ProposalDraft::select('proposal_draft_index')
            ->whereRaw('SUBSTRING(proposal_draft_defined_id, 1, 12) = ?', [$validatedData['id_event']])
            ->orderBy('proposal_draft_index', 'desc')
            ->first();

        if (is_null($latestIndex)) {
            $latestIndex = ProposalHelper::defined_id(1, 3) . ProposalHelper::defined_id(0, 2); // 00100
        } else {
            $substring = substr($latestIndex->proposal_draft_index, 0, 3);
            $integerValue = intval($substring) + 1;
            $latestIndex = ProposalHelper::defined_id($integerValue, 3) . ProposalHelper::defined_id(0, 2);
        }

        $definedId = $validatedData['id_event'] . $latestIndex;

        $fileName = $definedId . '.pdf';
        if ($uploadedFile) {
            $ext = $uploadedFile->getClientOriginalExtension();
            $fileName = $definedId . '.' . $ext;
            $uploadedFile->storeAs('proposal', $fileName);
        }

        $create = [
            'proposal_draft_defined_id' => $definedId,
            'proposal_draft_index' => $latestIndex,
            'id_event' => $validatedData['id_event'],
            'creative_member' => $validatedData['creative_member'] ?? Auth::id(),
            'proposal_draft_deadline_payment' => $validatedData['proposal_draft_deadline_payment'],
            'proposal_draft_note_member' => $validatedData['proposal_draft_note_member'] ?? 'not set',
            'proposal_draft_note_admin' => 'not set',
            'proposal_draft_status' => 2,
            'file_attached_name' => $fileName,
        ];

        $draft = ProposalDraft::create($create);

        if (! empty($validatedData['vendors']) && is_array($validatedData['vendors'])) {
            foreach ($validatedData['vendors'] as $v) {
                Vendor::create([
                    'id_proposal_draft' => $definedId,
                    'vendor_name' => $v['vendor_name'],
                    'vendor_contact' => $v['vendor_contact'] ?? 'not set',
                    'vendor_email' => $v['vendor_email'] ?? 'not set',
                    'vendor_sub_total' => $v['vendor_sub_total'] ?? 'not set',
                ]);
            }
        }

        return $draft;
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\CreativeMember\RelationManagers\ProposalSubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\CreativeMember\Pages\ListProposalDrafts::route('/'),
            'create' => \App\Filament\Resources\CreativeMember\Pages\CreateProposalDraft::route('/create'),
            'edit' => \App\Filament\Resources\CreativeMember\Pages\EditProposalDraft::route('/{record}/edit'),
            'view' => \App\Filament\Resources\CreativeMember\Pages\ViewProposalDraft::route('/{record}'),
        ];
    }
}

