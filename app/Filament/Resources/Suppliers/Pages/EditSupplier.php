<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Supplier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->authorize('view'),
            DeleteAction::make()
                ->authorize('delete')
                ->disabled(fn (Supplier $record): bool => $record->stockMovements()->exists())
                ->tooltip('Suppliers linked to stock history cannot be deleted.'),
        ];
    }
}
