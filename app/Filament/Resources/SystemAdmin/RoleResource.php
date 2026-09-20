<?php

namespace App\Filament\Resources\SystemAdmin;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Role as RoleModel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class RoleResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = RoleModel::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Roles';
    protected static string|\UnitEnum|null $navigationGroup = 'System Admin';
    protected static ?int $navigationSort = 3;

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
            TextInput::make('role_defined_id')
                ->label('ID Role (3 char)')
                ->required()
                ->maxLength(3)
                ->minLength(3)
                ->unique(ignoreRecord: true)
                ->placeholder('001'),
            TextInput::make('role_name')
                ->label('Role Name')
                ->required()
                ->maxLength(20)
                ->placeholder('System Admin'),
            Toggle::make('role_active_status')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('role_defined_id')->label('ID')->searchable()->sortable(),
            TextColumn::make('role_name')->label('Role')->searchable()->sortable(),
            ToggleColumn::make('role_active_status')->label('Aktif'),
            TextColumn::make('role_active_status')
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
            'index' => \App\Filament\Resources\SystemAdmin\Pages\ListRoles::route('/'),
            'create' => \App\Filament\Resources\SystemAdmin\Pages\CreateRole::route('/create'),
            'edit' => \App\Filament\Resources\SystemAdmin\Pages\EditRole::route('/{record}/edit'),
        ];
    }
}


