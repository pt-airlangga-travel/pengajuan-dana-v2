<?php

namespace App\Filament\Resources\SystemAdmin;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Division;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class DivisionResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = Division::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Divisions';
    protected static string|\UnitEnum|null $navigationGroup = 'System Admin';
    protected static ?int $navigationSort = 4;

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
            TextInput::make('division_defined_id')
                ->label('ID Division (3 char)')
                ->required()
                ->maxLength(3)
                ->minLength(3)
                ->unique(ignoreRecord: true)
                ->placeholder('001'),
            TextInput::make('division_name')
                ->label('Division Name')
                ->required()
                ->maxLength(255),
            Toggle::make('division_active_status')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('division_defined_id')->label('ID')->searchable()->sortable(),
            TextColumn::make('division_name')->label('Division')->searchable()->sortable(),
            ToggleColumn::make('division_active_status')->label('Aktif'),
            TextColumn::make('division_active_status')
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
            'index' => \App\Filament\Resources\SystemAdmin\Pages\ListDivisions::route('/'),
            'create' => \App\Filament\Resources\SystemAdmin\Pages\CreateDivision::route('/create'),
            'edit' => \App\Filament\Resources\SystemAdmin\Pages\EditDivision::route('/{record}/edit'),
        ];
    }
}


