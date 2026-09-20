<?php

namespace App\Filament\Resources\Shared;

use App\Models\Event;
use App\Models\Institution;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Shared: semua role butuh lihat Event (mirip v2 event index per role)
 * Tidak di-filter role agar semua bisa akses read-only
 */
class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = 'Master';
    protected static ?string $navigationLabel = 'Events';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                TextInput::make('event_name')->required()->maxLength(100),
                TextInput::make('event_short_name')->label('Short Name')->required()->maxLength(12)->helperText('Slug otomatis dari ini'),
                Select::make('id_institution')
                    ->label('Institution')
                    ->options(fn () => Institution::pluck('institution_name', 'institution_defined_id'))
                    ->searchable()->preload()->native(false)->required(),
                DatePicker::make('event_started_at')->label('Mulai')->native(false),
                DatePicker::make('event_finished_at')->label('Selesai')->native(false),
                Toggle::make('event_availability')->label('Available')->default(true),

            ])
            ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('#')->rowIndex(),
            TextColumn::make('event_name')->label('Nama Event')->searchable(),
            TextColumn::make('institution.institution_name')->label('Institusi')->searchable(),
            TextColumn::make('event_started_at_formatted')->label('Mulai'),
            TextColumn::make('event_finished_at_formatted')->label('Selesai'),
            // TextColumn::make('event_availability')->label('Available')->badge()->formatStateUsing(fn($s)=>$s?'1':'0')->color(fn($s)=>$s?'success':'danger'),
            IconColumn::make('event_availability')->label('Available')->boolean()->alignCenter(),
        ])
        ->recordUrl(null)
        ->striped()
        ->defaultSort('created_at', 'desc')
        ->recordActions([
            Action::make('daftarDraft')
                ->label('Lihat Pengajuan')
                ->button()
                ->size('xs')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary')
                ->url(function ($record) {
                    $user = \Illuminate\Support\Facades\Auth::user();
                    $role = $user?->id_role;
                    $eventId = $record->event_defined_id;
                    $base = match ($role) {
                        '003' => "/dashboard/organizer-admin/proposal-drafts",
                        '004' => "/dashboard/inspiring-manager/proposal-drafts",
                        default => "/dashboard/creative-member/proposal-drafts",
                    };
                    return $base . "?filters[id_event][value]={$eventId}";
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Shared\Pages\ListEvents::route('/'),
            'create' => \App\Filament\Resources\Shared\Pages\CreateEvent::route('/create'),
            'edit' => \App\Filament\Resources\Shared\Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}


