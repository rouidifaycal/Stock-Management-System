<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('has_products')
                    ->label('Has products')
                    ->placeholder('All categories')
                    ->trueLabel('With products')
                    ->falseLabel('Without products')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('products'),
                        false: fn (Builder $query): Builder => $query->doesntHave('products'),
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
                    ->disabled(fn (Category $record): bool => $record->products()->exists())
                    ->tooltip('Categories with products cannot be deleted.'),
            ])
            ->toolbarActions([]);
    }
}
