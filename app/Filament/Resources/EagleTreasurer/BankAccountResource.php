<?php

namespace App\Filament\Resources\EagleTreasurer;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\BankAccount;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    use HasRoleNavigation;
    protected static ?string $model = BankAccount::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|\UnitEnum|null $navigationGroup = 'Eagle Treasurer';
    protected static ?string $navigationLabel = 'Bank Account (Alias)';
    protected static ?int $navigationSort = 99;

    public static function shouldRegisterNavigation(): bool { return false; }
    public static function canAccess(): bool { return static::hasRole(Role::EagleTreasurer); }
    public static function form(Schema $schema): Schema { return $schema->schema([]); }
    public static function table(Table $table): Table { return BankDetailResource::table($table); }
    public static function getPages(): array { return []; }
}

