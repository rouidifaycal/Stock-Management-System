<?php

namespace App\Filament\Widgets;

use App\Services\InventoryDashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class StockActivityOverview extends BaseWidget
{
    use InteractsWithPageFilters;

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
        $activity = $this->dashboardService->stockActivity($this->pageFilters['period'] ?? '30');

        return [
            Stat::make('Stock entries', $activity['entries'])
                ->description("{$activity['entry_units']} units received, {$activity['period']}"),
            Stat::make('Stock exits', $activity['exits'])
                ->description("{$activity['exit_units']} units removed, {$activity['period']}"),
        ];
    }
}
