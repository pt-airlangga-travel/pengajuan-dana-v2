<?php

namespace App\Filament\Resources\SystemAdmin;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Division;
use App\Models\Role as RoleModel;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class UserResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Users';
    protected static string|\UnitEnum|null $navigationGroup = 'System Admin';
    protected static ?int $navigationSort = 1;

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
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nama'),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->label('Email'),
            TextInput::make('position')
                ->required()
                ->maxLength(255)
                ->label('Position'),
            Select::make('id_role')
                ->label('Role')
                ->options(fn () => RoleModel::where('role_active_status', true)->pluck('role_name', 'role_defined_id'))
                ->searchable()
                ->preload()
                ->required()
                ->native(false),
            Select::make('id_division')
                ->label('Division')
                ->options(fn () => Division::where('division_active_status', true)->pluck('division_name', 'division_defined_id'))
                ->searchable()
                ->preload()
                ->required()
                ->native(false),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->minLength(6)
                ->dehydrated(fn ($state): bool => filled($state))
                ->label('Password'),
            Toggle::make('active_status')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('email')->searchable()->sortable(),
            TextColumn::make('position')->searchable()->toggleable(),
            TextColumn::make('id_role')->label('Role')->badge()->searchable(),
            TextColumn::make('role.role_name')->label('Role Name')->toggleable()->placeholder('-'),
            TextColumn::make('id_division')->label('Division')->badge()->toggleable(),
            TextColumn::make('division.division_name')->label('Division Name')->toggleable()->placeholder('-'),
            ToggleColumn::make('active_status')->label('Aktif'),
            TextColumn::make('active_status')
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
            'index' => \App\Filament\Resources\SystemAdmin\Pages\ListUsers::route('/'),
            'create' => \App\Filament\Resources\SystemAdmin\Pages\CreateUser::route('/create'),
            'edit' => \App\Filament\Resources\SystemAdmin\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}


