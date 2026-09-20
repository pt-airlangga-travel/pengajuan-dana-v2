<?php

namespace App\Filament\Resources\SystemAdmin;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Event;
use App\Models\Institution;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;

class EventResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = Event::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Events';
    protected static string|\UnitEnum|null $navigationGroup = 'System Admin';
    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasRole(Role::SystemAdmin);
    }

    public static function canAccess(): bool
    {
        return static::hasRole(Role::SystemAdmin);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('event_name')
                ->label('Event Name')
                ->required()
                ->maxLength(100),
            TextInput::make('event_short_name')
                ->label('Short Name')
                ->required()
                ->maxLength(12)
                ->helperText('Slug otomatis diisi model (Str::slug)'),
            Select::make('id_institution')
                ->label('Institution')
                ->options(fn () => Institution::where('institution_active_status', true)->pluck('institution_name', 'institution_defined_id'))
                ->searchable()
                ->preload()
                ->required()
                ->native(false),
            TextInput::make('event_date')
                ->label('Date (auto)')
                ->maxLength(20)
                ->placeholder('1 atau 1-3')
                ->helperText('Diisi otomatis dari start/finish di v2'),
            TextInput::make('event_month')
                ->label('Month')
                ->maxLength(20)
                ->placeholder('January atau jan-feb'),
            TextInput::make('event_year')
                ->label('Year')
                ->maxLength(20)
                ->placeholder('2024 atau 2024-2025'),
            DatePicker::make('event_started_at')
                ->label('Mulai')
                ->required()
                ->native(false),
            DatePicker::make('event_finished_at')
                ->label('Selesai')
                ->required()
                ->native(false),
            Toggle::make('event_availability')
                ->label('Available')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('event_defined_id')->label('ID')->searchable()->sortable(),
            TextColumn::make('event_name')->label('Event')->searchable()->sortable(),
            TextColumn::make('event_short_name')->label('Short')->searchable()->toggleable(),
            TextColumn::make('event_name_slug')->label('Slug')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('institution.institution_name')->label('Institution')->searchable()->placeholder('-'),
            TextColumn::make('event_started_at')->label('Mulai')->date()->sortable(),
            TextColumn::make('event_finished_at')->label('Selesai')->date()->sortable(),
            TextColumn::make('event_started_at_formatted')->label('Mulai (fmt)')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('event_finished_at_formatted')->label('Selesai (fmt)')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('event_date')->label('Date')->toggleable(),
            TextColumn::make('event_month')->label('Month')->toggleable(),
            TextColumn::make('event_year')->label('Year')->toggleable(),
            ToggleColumn::make('event_availability')->label('Available'),
            TextColumn::make('event_availability')
                ->label('Avail Badge')
                ->badge()
                ->formatStateUsing(fn ($state) => $state ? 'Ya' : 'Tidak')
                ->color(fn ($state) => $state ? 'success' : 'danger')
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
    }

                public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SystemAdmin\Pages\ListEvents::route('/'),
            'create' => \App\Filament\Resources\SystemAdmin\Pages\CreateEvent::route('/create'),
            'edit' => \App\Filament\Resources\SystemAdmin\Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}


