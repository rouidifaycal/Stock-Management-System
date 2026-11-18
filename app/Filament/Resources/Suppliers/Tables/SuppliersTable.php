<?php

namespace App\Filament\Resources\Suppliers\Tables;

use App\Models\Supplier;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('stock_movements_count')
                    ->counts('stockMovements')
                    ->label('Stock operations')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('has_stock_movements')
                    ->label('Has stock history')
                    ->placeholder('All suppliers')
                    ->trueLabel('With stock history')
                    ->falseLabel('Without stock history')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('stockMovements'),
                        false: fn (Builder $query): Builder => $query->doesntHave('stockMovements'),
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
                    ->disabled(fn (Supplier $record): bool => $record->stockMovements()->exists())
                    ->tooltip('Suppliers linked to stock history cannot be deleted.'),
            ])
            ->toolbarActions([]);
    }
}
