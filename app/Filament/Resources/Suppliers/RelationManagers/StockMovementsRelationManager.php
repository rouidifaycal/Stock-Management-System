<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Filament\Resources\StockMovements\Tables\StockMovementsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $relatedResource = StockMovementResource::class;

    public function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
    }
}
