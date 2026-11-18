<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('image')
                    ->disk('public')
                    ->imageHeight(160),
                TextEntry::make('name'),
                TextEntry::make('sku'),
                TextEntry::make('category.name')
                    ->label('Category'),
                TextEntry::make('description')
                    ->placeholder('No description provided.'),
                TextEntry::make('price')
                    ->numeric(decimalPlaces: 2),
                TextEntry::make('quantity')
                    ->label('Current stock')
                    ->state(fn (Product $record): int => $record->quantity),
                TextEntry::make('min_stock')
                    ->label('Minimum stock'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
