<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\InventoryDashboardService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class LowStockProducts extends TableWidget
{
    protected static ?string $heading = 'Low-stock products';

    protected int|string|array $columnSpan = 'full';

    protected InventoryDashboardService $dashboardService;

    public static function canView(): bool
    {
        return Gate::allows('viewInventoryReports');
    }

    public function boot(InventoryDashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->dashboardService->lowStockProducts())
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Current stock')
                    ->suffix(' units')
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Minimum stock')
                    ->suffix(' units')
                    ->sortable(),
                TextColumn::make('stock_status')
                    ->label('Status')
                    ->state(fn (Product $record): string => $record->quantity === 0 ? 'Out of stock' : 'Low stock')
                    ->badge()
                    ->color(fn (Product $record): string => $record->quantity === 0 ? 'danger' : 'warning'),
            ])
            ->emptyStateHeading('No low-stock products')
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
