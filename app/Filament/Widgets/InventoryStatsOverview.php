<?php

namespace App\Filament\Widgets;

use App\Services\InventoryDashboardService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class InventoryStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected InventoryDashboardService $dashboardService;

    public static function canView(): bool
    {
        return Gate::allows('viewInventoryReports');
    }

    public function boot(InventoryDashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    protected function getStats(): array
    {
        $statistics = $this->dashboardService->statistics();

        return [
            Stat::make('Total products', $statistics['products'])
                ->icon(Heroicon::OutlinedCube)
                ->color('info'),
            Stat::make('Categories', $statistics['categories'])
                ->icon(Heroicon::OutlinedTag)
                ->color('info'),
            Stat::make('Suppliers', $statistics['suppliers'])
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary'),
            Stat::make('Units in stock', $statistics['units'])
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('primary'),
            Stat::make('Inventory value', $statistics['inventory_value'])
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('info')
                ->description('Recorded price multiplied by current quantity'),
            Stat::make('Normal stock', $statistics['normal'])
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Low stock', $statistics['low'])
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->description('Above zero and at or below minimum')
                ->color('warning'),
            Stat::make('Out of stock', $statistics['out_of_stock'])
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger'),
        ];
    }
}
