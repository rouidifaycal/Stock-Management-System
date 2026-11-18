<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'entry' ? 'Stock entry' : 'Stock exit')
                    ->color(fn (string $state): string => $state === 'entry' ? 'success' : 'warning')
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->suffix(' units')
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Recorded by')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'entry' => 'Stock entry',
                        'exit' => 'Stock exit',
                    ]),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable(),
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize('view'),
            ])
            ->toolbarActions([])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['product', 'supplier', 'user']))
            ->defaultSort('created_at', 'desc');
    }
}
