<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->square(),
                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Current stock')
                    ->badge()
                    ->color(fn (Product $record): string => match (true) {
                        $record->quantity === 0 => 'danger',
                        $record->quantity <= $record->min_stock => 'warning',
                        default => 'success',
                    })
                    ->description(fn (Product $record): string => "Minimum: {$record->min_stock}")
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable(),
                TernaryFilter::make('low_stock')
                    ->label('Low stock')
                    ->placeholder('All stock levels')
                    ->trueLabel('At or below minimum')
                    ->falseLabel('Above minimum')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereColumn('quantity', '<=', 'min_stock'),
                        false: fn (Builder $query): Builder => $query->whereColumn('quantity', '>', 'min_stock'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize('view'),
                EditAction::make()
                    ->authorize('update'),
                DeleteAction::make()
                    ->authorize('delete')
                    ->disabled(fn (Product $record): bool => $record->stockMovements()->exists())
                    ->tooltip('Products with stock history cannot be deleted.'),
            ])
            ->toolbarActions([]);
    }
}
