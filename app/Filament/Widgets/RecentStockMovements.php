<?php

namespace App\Filament\Widgets;

use App\Services\InventoryDashboardService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class RecentStockMovements extends TableWidget
{
    protected static ?string $heading = 'Recent stock movements';

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
            ->query(fn (): Builder => $this->dashboardService->recentMovements())
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Movement')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'entry' ? 'Stock entry' : 'Stock exit')
                    ->color(fn (string $state): string => $state === 'entry' ? 'success' : 'warning'),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->suffix(' units')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Recorded by')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Date and time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
