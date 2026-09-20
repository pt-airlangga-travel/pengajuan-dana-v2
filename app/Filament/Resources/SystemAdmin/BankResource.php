<?php

namespace App\Filament\Resources\SystemAdmin;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Bank;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class BankResource extends Resource
{
    use HasRoleNavigation;
    protected static ?string $model = Bank::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Banks';
    protected static string|\UnitEnum|null $navigationGroup = 'System Admin';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool { return static::hasRole(Role::SystemAdmin); }
    public static function canAccess(): bool { return static::hasRole(Role::SystemAdmin); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('bank_defined_id')
                ->label('ID Bank (3 char)')
                ->required()
                ->maxLength(3)
                ->unique(ignoreRecord: true)
                ->placeholder('001'),
            TextInput::make('bank_name')
                ->label('Bank Name')
                ->required()
                ->maxLength(255),
            Toggle::make('bank_active_status')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('bank_defined_id')->label('ID')->searchable()->sortable(),
            TextColumn::make('bank_name')->searchable()->sortable(),
            ToggleColumn::make('bank_active_status')->label('Aktif'),
            TextColumn::make('bank_active_status')
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
            'index' => \App\Filament\Resources\SystemAdmin\Pages\ListBanks::route('/'),
            'create' => \App\Filament\Resources\SystemAdmin\Pages\CreateBank::route('/create'),
            'edit' => \App\Filament\Resources\SystemAdmin\Pages\EditBank::route('/{record}/edit'),
        ];
    }
}


