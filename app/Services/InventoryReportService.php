<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class InventoryReportService
{
    /**
     * @return Builder<Product>
     */
    public function inventory(): Builder
    {
        Gate::authorize('viewInventoryReports');

        return Product::query()
            ->with('category')
            ->select('products.*')
            ->selectRaw('(products.price * products.quantity) AS inventory_value');
    }

    /**
     * @return Builder<StockMovement>
     */
    public function stockMovements(): Builder
    {
        Gate::authorize('viewInventoryReports');

        return StockMovement::query()
            ->with(['product.category', 'supplier', 'user'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
