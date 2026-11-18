<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Models\Product;
use App\Models\Supplier;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class StockMovementForm
{
    private const MAX_STOCK = 4294967295;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::fields());
    }

    /**
     * @return array<Step>
     */
    public static function steps(): array
    {
        return [
            Step::make('Operation details')
                ->description('Choose the stock operation and enter its details.')
                ->schema(self::fields()),
            Step::make('Review and confirm')
                ->description('Verify the stock change before recording it.')
                ->schema([
                    Text::make(fn (Get $get): string => match ($get('type')) {
                        'entry' => 'Operation: Stock entry',
                        'exit' => 'Operation: Stock exit',
                        default => 'Operation: Not selected',
                    }),
                    Text::make(fn (Get $get): string => self::productLabel($get)),
                    Text::make(fn (Get $get): string => self::currentStockLabel($get)),
                    Text::make(fn (Get $get): string => self::quantityLabel($get)),
                    Text::make(fn (Get $get): string => self::projectedStockLabel($get)),
                    Text::make(fn (Get $get): string => self::supplierLabel($get))
                        ->visible(fn (Get $get): bool => $get('type') === 'entry'),
                    Text::make(fn (Get $get): string => self::reasonLabel($get))
                        ->visible(fn (Get $get): bool => $get('type') === 'exit'),
                    Text::make(fn (Get $get): string => self::notesLabel($get)),
                ]),
        ];
    }

    /**
     * @return array<Component>
     */
    private static function fields(): array
    {
        return [
            Select::make('type')
                ->label('Operation')
                ->options([
                    'entry' => 'Stock entry',
                    'exit' => 'Stock exit',
                ])
                ->required()
                ->live(),
            Select::make('product_id')
                ->relationship('product', 'name')
                ->getOptionLabelFromRecordUsing(fn (Product $record): string => "{$record->sku} - {$record->name}")
                ->searchable(['name', 'sku'])
                ->required()
                ->live(),
            Text::make(fn (Get $get): string => self::currentStockLabel($get))
                ->visible(fn (Get $get): bool => filled($get('product_id'))),
            TextInput::make('quantity')
                ->label('Quantity')
                ->integer()
                ->minValue(1)
                ->maxValue(fn (Get $get): int => self::maximumQuantity($get))
                ->required(),
            Select::make('supplier_id')
                ->relationship('supplier', 'name')
                ->searchable()
                ->visible(fn (Get $get): bool => $get('type') === 'entry')
                ->dehydrated(fn (Get $get): bool => $get('type') === 'entry'),
            TextInput::make('reason')
                ->maxLength(255)
                ->visible(fn (Get $get): bool => $get('type') === 'exit')
                ->dehydrated(fn (Get $get): bool => $get('type') === 'exit'),
            Textarea::make('notes')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    private static function maximumQuantity(Get $get): int
    {
        $product = self::selectedProduct($get);

        if ($product === null) {
            return self::MAX_STOCK;
        }

        return $get('type') === 'exit'
            ? $product->quantity
            : self::MAX_STOCK - $product->quantity;
    }

    private static function selectedProduct(Get $get): ?Product
    {
        $productId = $get('product_id');

        return filled($productId)
            ? Product::query()->whereKey($productId)->first()
            : null;
    }

    private static function productLabel(Get $get): string
    {
        $product = self::selectedProduct($get);

        return $product === null
            ? 'Product: Not selected'
            : "Product: {$product->sku} - {$product->name}";
    }

    private static function currentStockLabel(Get $get): string
    {
        $product = self::selectedProduct($get);

        return $product === null
            ? 'Current stock: Not available'
            : "Current stock: {$product->quantity} units";
    }

    private static function quantityLabel(Get $get): string
    {
        $quantity = $get('quantity');

        return filled($quantity)
            ? 'Quantity: '.(int) $quantity.' units'
            : 'Quantity: Not entered';
    }

    private static function projectedStockLabel(Get $get): string
    {
        $product = self::selectedProduct($get);
        $quantity = $get('quantity');

        if ($product === null || ! filled($quantity)) {
            return 'Projected stock: Not available';
        }

        $quantity = (int) $quantity;

        if ($get('type') === 'exit' && $quantity > $product->quantity) {
            return "Projected stock: Exit exceeds available stock ({$product->quantity} units)";
        }

        $projectedQuantity = $get('type') === 'entry'
            ? $product->quantity + $quantity
            : $product->quantity - $quantity;

        return "Projected stock: {$projectedQuantity} units";
    }

    private static function supplierLabel(Get $get): string
    {
        $supplierId = $get('supplier_id');
        $supplierName = filled($supplierId)
            ? Supplier::query()->whereKey($supplierId)->value('name')
            : null;

        return $supplierName === null
            ? 'Supplier: None selected'
            : "Supplier: {$supplierName}";
    }

    private static function reasonLabel(Get $get): string
    {
        $reason = $get('reason');

        return filled($reason)
            ? 'Reason: '.(string) $reason
            : 'Reason: Not provided';
    }

    private static function notesLabel(Get $get): string
    {
        $notes = $get('notes');

        return filled($notes)
            ? 'Notes: '.(string) $notes
            : 'Notes: None';
    }
}
