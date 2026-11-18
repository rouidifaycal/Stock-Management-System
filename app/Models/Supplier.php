<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'phone', 'address', 'company_name'])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Supplier $supplier): void {
            if ($supplier->stockMovements()->exists()) {
                throw new AuthorizationException('Suppliers with stock movement history cannot be deleted.');
            }
        });
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
