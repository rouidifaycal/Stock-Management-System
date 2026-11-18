<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Models\Supplier;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('company_name')
                    ->placeholder('No company specified.'),
                TextEntry::make('email')
                    ->placeholder('No email address provided.'),
                TextEntry::make('phone')
                    ->placeholder('No phone number provided.'),
                TextEntry::make('address')
                    ->placeholder('No address provided.')
                    ->columnSpanFull(),
                TextEntry::make('stock_movements_count')
                    ->label('Related stock operations')
                    ->state(fn (Supplier $record): int => $record->stockMovements()->count()),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
