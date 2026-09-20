<?php

namespace App\Filament\Resources\SystemAdmin;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\BankAsal;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;

class BankAsalResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = BankAsal::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Bank Asal';
    protected static string|\UnitEnum|null $navigationGroup = 'System Admin';
    protected static ?int $navigationSort = 7;

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
            TextInput::make('bank_name')
                ->label('Bank Name')
                ->required()
                ->maxLength(255)
                ->placeholder('Mandiri Giro'),
            TextInput::make('no_rekening')
                ->label('No Rekening')
                ->maxLength(255)
                ->placeholder('1420014668502'),
            TextInput::make('color')
                ->label('Color')
                ->required()
                ->maxLength(255)
                ->placeholder('red / green / blue / hex #ff0000')
                ->helperText('Seeder v2: red, green, blue, yellow, pink, orange — atau hex'),
            ColorPicker::make('color_picker')
                ->label('Color Picker (hex)')
                ->dehydrated(false)
                ->helperText('Opsional: pilih warna lalu copy hex ke field Color di atas'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('bank_name')->label('Bank')->searchable()->sortable(),
            TextColumn::make('no_rekening')->label('No Rekening')->searchable()->copyable(),
            TextColumn::make('color')->label('Color')->badge()->searchable()->color(fn ($state) => match (strtolower((string) $state)) {
                'red' => 'danger',
                'green' => 'success',
                'blue' => 'info',
                'yellow' => 'warning',
                'pink' => 'pink',
                'orange' => 'warning',
                default => 'gray',
            }),
        ]);
    }

                public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SystemAdmin\Pages\ListBankAsals::route('/'),
            'create' => \App\Filament\Resources\SystemAdmin\Pages\CreateBankAsal::route('/create'),
            'edit' => \App\Filament\Resources\SystemAdmin\Pages\EditBankAsal::route('/{record}/edit'),
        ];
    }
}


