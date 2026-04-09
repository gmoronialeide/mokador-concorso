<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\StoreResource;
use App\Models\Store;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopStoresChartWidget extends BaseWidget
{
    protected static ?string $heading = 'Top 5 Punti Vendita';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Store::query()
                    ->withCount('plays')
                    ->whereHas('plays')
                    ->orderByDesc('plays_count')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('display_name')
                    ->label('Punto Vendita')
                    ->url(fn (Store $record): string => StoreResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('city')->label('Città'),
                TextColumn::make('province')->label('Prov.'),
                TextColumn::make('plays_count')
                    ->label('Giocate')
                    ->badge()
                    ->color('primary'),
            ])
            ->paginated(false);
    }
}
