<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                Section::make('Anagrafica')
                    ->schema([
                        TextInput::make('code')->label('Codice')
                            ->required()->unique(ignoreRecord: true)->maxLength(50),
                        TextInput::make('name')->label('Ragione sociale')
                            ->required()->maxLength(255),
                        TextInput::make('sign_name')->label('Insegna locale')
                            ->required()->maxLength(255),
                        TextInput::make('vat_number')->label('Partita IVA')
                            ->required()->maxLength(20),
                        TextInput::make('agent')->label('Agente')
                            ->maxLength(255),
                    ])->columns(2),
                Section::make('Indirizzo')
                    ->schema([
                        TextInput::make('address')->label('Indirizzo')
                            ->required()->maxLength(255),
                        TextInput::make('city')->label('Città')
                            ->required()->maxLength(100),
                        TextInput::make('province')->label('Provincia')
                            ->required()->length(2)->maxLength(2),
                        TextInput::make('cap')->label('CAP')
                            ->maxLength(5),
                    ])->columns(2),
                Toggle::make('is_active')->label('Attivo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Codice')->searchable()->sortable(),
                TextColumn::make('name')->label('Ragione sociale')->searchable()->sortable()
                    ->toggleable(),
                TextColumn::make('sign_name')->label('Insegna')->searchable()->sortable(),
                TextColumn::make('vat_number')->label('P.IVA')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('agent')->label('Agente')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')->label('Indirizzo')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')->label('Città')->searchable()->sortable(),
                TextColumn::make('province')->label('Prov.')->sortable(),
                IconColumn::make('is_active')->label('Attivo')->boolean(),
            ])
            ->defaultSort('sign_name')
            ->filters([
                TernaryFilter::make('is_active')->label('Attivo'),
                SelectFilter::make('province')->label('Provincia')
                    ->options(fn () => Store::distinct()->pluck('province', 'province')->filter()->sort()->toArray()),
                SelectFilter::make('agent')->label('Agente')
                    ->options(fn () => Store::whereNotNull('agent')->distinct()->pluck('agent', 'agent')->filter()->sort()->toArray()),
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
