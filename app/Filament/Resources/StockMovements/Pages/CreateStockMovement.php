<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Resources\StockMovements\Schemas\StockMovementForm;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockMovementService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class CreateStockMovement extends CreateRecord
{
    use HasWizard;

    protected static string $resource = StockMovementResource::class;

    protected StockMovementService $stockMovementService;

    public function boot(StockMovementService $stockMovementService): void
    {
        $this->stockMovementService = $stockMovementService;
    }

    /**
     * @return array<Step>
     */
    public function getSteps(): array
    {
        return StockMovementForm::steps();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException('An authenticated user is required to record stock movements.');
        }

        return $this->stockMovementService->record($user, $data);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Confirm stock operation');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->record instanceof StockMovement && $this->record->type === 'entry'
            ? 'Stock entry recorded successfully.'
            : 'Stock exit recorded successfully.';
    }
}
