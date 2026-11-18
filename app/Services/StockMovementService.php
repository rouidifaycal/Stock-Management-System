<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    private const MAX_STOCK = 4294967295;

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(User $user, array $data): StockMovement
    {
        Gate::forUser($user)->authorize('create', StockMovement::class);

        $validated = Validator::make($data, [
            'type' => ['required', Rule::in(['entry', 'exit'])],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_STOCK],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $type = (string) $validated['type'];
        $productId = (int) $validated['product_id'];
        $quantity = (int) $validated['quantity'];
        $supplierId = filled($validated['supplier_id'] ?? null)
            ? (int) $validated['supplier_id']
            : null;

        if ($type === 'exit' && $supplierId !== null) {
            throw ValidationException::withMessages([
                'supplier_id' => 'A supplier can only be recorded for a stock entry.',
            ]);
        }

        $reason = isset($validated['reason']) ? (string) $validated['reason'] : null;
        $notes = isset($validated['notes']) ? (string) $validated['notes'] : null;

        return DB::transaction(function () use (
            $user,
            $type,
            $productId,
            $quantity,
            $supplierId,
            $reason,
            $notes,
        ): StockMovement {
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            if ($type === 'exit' && $quantity > $product->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The stock exit quantity exceeds the available stock.',
                ]);
            }

            if ($type === 'entry' && $quantity > self::MAX_STOCK - $product->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The stock entry exceeds the maximum quantity supported for this product.',
                ]);
            }

            $movement = StockMovement::query()->create([
                'product_id' => $product->getKey(),
                'user_id' => $user->getKey(),
                'supplier_id' => $type === 'entry' ? $supplierId : null,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $type === 'exit' ? $reason : null,
                'notes' => $notes,
            ]);

            if ($type === 'entry') {
                $product->increment('quantity', $quantity);
            } else {
                $product->decrement('quantity', $quantity);
            }

            return $movement;
        }, attempts: 3);
    }
}
