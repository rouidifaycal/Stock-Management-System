<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StockMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'entry' ? 'Stock entry' : 'Stock exit')
                    ->color(fn (string $state): string => $state === 'entry' ? 'success' : 'warning'),
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('product.sku')
                    ->label('SKU'),
                TextEntry::make('quantity')
                    ->suffix(' units'),
                TextEntry::make('supplier.name')
                    ->placeholder('No supplier recorded.'),
                TextEntry::make('user.name')
                    ->label('Recorded by'),
                TextEntry::make('reason')
                    ->placeholder('No reason provided.'),
                TextEntry::make('notes')
                    ->placeholder('No notes provided.')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
