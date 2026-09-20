<?php

namespace App\Filament\Resources\SystemAdmin;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Institution;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;

class InstitutionResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = Institution::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Institutions';
    protected static string|\UnitEnum|null $navigationGroup = 'System Admin';
    protected static ?int $navigationSort = 5;

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
            TextInput::make('institution_defined_id')
                ->label('ID Institution (3 char)')
                ->required()
                ->maxLength(3)
                ->minLength(3)
                ->unique(ignoreRecord: true)
                ->placeholder('001'),
            TextInput::make('institution_name')
                ->label('Institution Name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set) {
                    if (filled($state)) {
                        $set('institution_name_slug', Str::slug($state));
                    }
                }),
            TextInput::make('institution_name_slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Otomatis dari nama, bisa diedit'),
            TextInput::make('institution_contact')
                ->label('Contact')
                ->maxLength(255)
                ->default('not set')
                ->placeholder('not set'),
            TextInput::make('institution_email')
                ->label('Email')
                ->email()
                ->maxLength(255)
                ->default('not set')
                ->placeholder('not set'),
            Toggle::make('institution_active_status')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('institution_defined_id')->label('ID')->searchable()->sortable(),
            TextColumn::make('institution_name')->label('Name')->searchable()->sortable(),
            TextColumn::make('institution_name_slug')->label('Slug')->searchable()->toggleable(),
            TextColumn::make('institution_contact')->label('Contact')->searchable()->toggleable(),
            TextColumn::make('institution_email')->label('Email')->searchable()->toggleable(),
            ToggleColumn::make('institution_active_status')->label('Aktif'),
            TextColumn::make('institution_active_status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Nonaktif')
                ->color(fn ($state) => $state ? 'success' : 'danger')
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
    }

                public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SystemAdmin\Pages\ListInstitutions::route('/'),
            'create' => \App\Filament\Resources\SystemAdmin\Pages\CreateInstitution::route('/create'),
            'edit' => \App\Filament\Resources\SystemAdmin\Pages\EditInstitution::route('/{record}/edit'),
        ];
    }
}


