<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';

    protected static string | \UnitEnum | null $navigationGroup = 'Anagrafiche';

    protected static ?string $modelLabel = 'Punto Vendita';

    protected static ?string $pluralModelLabel = 'Punti Vendita';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('code')->label('Codice')
                    ->required()->unique(ignoreRecord: true)->maxLength(50),
                TextInput::make('name')->label('Nome / Ragione sociale')
                    ->required()->maxLength(255),
                TextInput::make('address')->label('Indirizzo')
                    ->required()->maxLength(255),
                TextInput::make('city')->label('Città')
                    ->required()->maxLength(100),
                TextInput::make('province')->label('Provincia')
                    ->required()->length(2)->maxLength(2),
                TextInput::make('cap')->label('CAP')
                    ->required()->length(5)->maxLength(5),
                Toggle::make('is_active')->label('Attivo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('code')->label('Codice')->searchable()->sortable(),
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('address')->label('Indirizzo'),
                TextColumn::make('city')->label('Città')->searchable(),
                TextColumn::make('province')->label('Prov.')->sortable(),
                IconColumn::make('is_active')->label('Attivo')->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')->label('Attivo'),
                SelectFilter::make('province')->label('Provincia')
                    ->options(fn () => Store::distinct()->pluck('province', 'province')->filter()->toArray()),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
