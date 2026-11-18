<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->authorize('view'),
            DeleteAction::make()
                ->authorize('delete')
                ->disabled(fn (Product $record): bool => $record->stockMovements()->exists())
                ->tooltip('Products with stock history cannot be deleted.'),
        ];
    }
}
