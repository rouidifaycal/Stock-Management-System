<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardWelcome;
use App\Filament\Widgets\InventoryStatsOverview;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\RecentStockMovements;
use App\Filament\Widgets\StockActivityOverview;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Inventory overview';

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            DashboardWelcome::class,
            InventoryStatsOverview::class,
            StockActivityOverview::class,
            LowStockProducts::class,
            RecentStockMovements::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        if (! Gate::allows('viewInventoryReports')) {
            return $schema;
        }

        return $schema
            ->components([
                Select::make('period')
                    ->label('Stock activity period')
                    ->options([
                        '7' => 'Last 7 days',
                        '30' => 'Last 30 days',
                        '90' => 'Last 90 days',
                    ])
                    ->default('30')
                    ->required()
                    ->native(false),
            ]);
    }
}
