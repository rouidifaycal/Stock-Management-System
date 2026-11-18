<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LogicException;

class InventoryDashboardService
{
    /**
     * @return array{
     *     products: int,
     *     categories: int,
     *     suppliers: int,
     *     units: int,
     *     inventory_value: string,
     *     normal: int,
     *     low: int,
     *     out_of_stock: int
     * }
     */
    public function statistics(): array
    {
        Gate::authorize('viewInventoryReports');

        $productStatistics = DB::table('products')
            ->selectRaw(
                'COUNT(*) AS products,
                COALESCE(SUM(quantity), 0) AS units,
                COALESCE(SUM(price * quantity), 0) AS inventory_value,
                COALESCE(SUM(CASE WHEN quantity > 0 AND quantity <= min_stock THEN 1 ELSE 0 END), 0) AS low,
                COALESCE(SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END), 0) AS out_of_stock,
                COALESCE(SUM(CASE WHEN quantity > min_stock THEN 1 ELSE 0 END), 0) AS normal'
            )
            ->first();

        if ($productStatistics === null) {
            throw new LogicException('The inventory statistics aggregate returned no result.');
        }

        return [
            'products' => (int) $productStatistics->products,
            'categories' => DB::table('categories')->count(),
            'suppliers' => DB::table('suppliers')->count(),
            'units' => (int) $productStatistics->units,
            'inventory_value' => (string) $productStatistics->inventory_value,
            'normal' => (int) $productStatistics->normal,
            'low' => (int) $productStatistics->low,
            'out_of_stock' => (int) $productStatistics->out_of_stock,
        ];
    }

    /**
     * @return Builder<Product>
     */
    public function lowStockProducts(): Builder
    {
        Gate::authorize('viewInventoryReports');

        return Product::query()
            ->whereColumn('quantity', '<=', 'min_stock')
            ->orderBy('quantity')
            ->orderBy('name');
    }

    /**
     * @return Builder<StockMovement>
     */
    public function recentMovements(): Builder
    {
        Gate::authorize('viewInventoryReports');

        return StockMovement::query()
            ->with(['product', 'user', 'supplier'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array{
     *     period: string,
     *     entries: int,
     *     entry_units: int,
     *     exits: int,
     *     exit_units: int
     * }
     */
    public function stockActivity(mixed $period): array
    {
        Gate::authorize('viewInventoryReports');

        if (! is_string($period) && ! is_int($period)) {
            throw ValidationException::withMessages([
                'period' => 'Select a supported stock activity period.',
            ]);
        }

        $days = match ((string) $period) {
            '7' => 7,
            '30' => 30,
            '90' => 90,
            default => throw ValidationException::withMessages([
                'period' => 'Select a supported stock activity period.',
            ]),
        };

        $end = now();
        $start = $end->copy()->startOfDay()->subDays($days - 1);
        $activity = DB::table('stock_movements')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN type = 'entry' THEN 1 ELSE 0 END), 0) AS entries,
                COALESCE(SUM(CASE WHEN type = 'entry' THEN quantity ELSE 0 END), 0) AS entry_units,
                COALESCE(SUM(CASE WHEN type = 'exit' THEN 1 ELSE 0 END), 0) AS exits,
                COALESCE(SUM(CASE WHEN type = 'exit' THEN quantity ELSE 0 END), 0) AS exit_units"
            )
            ->first();

        if ($activity === null) {
            throw new LogicException('The stock activity aggregate returned no result.');
        }

        return [
            'period' => "Last {$days} days",
            'entries' => (int) $activity->entries,
            'entry_units' => (int) $activity->entry_units,
            'exits' => (int) $activity->exits,
            'exit_units' => (int) $activity->exit_units,
        ];
    }
}
