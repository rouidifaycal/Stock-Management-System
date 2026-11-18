<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->authorize('view'),
            DeleteAction::make()
                ->authorize('delete')
                ->disabled(fn (Category $record): bool => $record->products()->exists())
                ->tooltip('Categories with products cannot be deleted.'),
        ];
    }
}
