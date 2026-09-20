<?php

namespace App\Filament\Resources\SystemAdmin;

use App\Enums\Role;
use App\Filament\Resources\Concerns\HasRoleNavigation;
use App\Models\Need;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class NeedResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = Need::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Needs';
    protected static string|\UnitEnum|null $navigationGroup = 'System Admin';
    protected static ?int $navigationSort = 6;

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
            TextInput::make('need_defined_id')
                ->label('ID Need (3 char)')
                ->required()
                ->maxLength(3)
                ->minLength(3)
                ->unique(ignoreRecord: true)
                ->placeholder('001'),
            TextInput::make('need_name')
                ->label('Need Name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Toggle::make('needs_active_status')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->columns([
            TextColumn::make('need_defined_id')->label('ID')->searchable()->sortable(),
            TextColumn::make('need_name')->label('Need')->searchable()->sortable(),
            ToggleColumn::make('needs_active_status')->label('Aktif'),
            TextColumn::make('needs_active_status')
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
            'index' => \App\Filament\Resources\SystemAdmin\Pages\ListNeeds::route('/'),
            'create' => \App\Filament\Resources\SystemAdmin\Pages\CreateNeed::route('/create'),
            'edit' => \App\Filament\Resources\SystemAdmin\Pages\EditNeed::route('/{record}/edit'),
        ];
    }
}


