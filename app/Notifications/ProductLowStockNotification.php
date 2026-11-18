<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

class ProductLowStockNotification extends Notification
{
    public function __construct(
        private readonly string $productName,
        private readonly string $sku,
        private readonly int $quantity,
        private readonly int $minimumStock,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Low stock alert')
            ->body("{$this->productName} ({$this->sku}) has {$this->quantity} units remaining. Minimum stock: {$this->minimumStock}.")
            ->warning()
            ->getDatabaseMessage();
    }
}
