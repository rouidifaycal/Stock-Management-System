<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case StockManager = 'stock_manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::StockManager => 'Stock Manager',
            self::Employee => 'Employee',
        };
    }

    public function canManageCatalog(): bool
    {
        return $this !== self::Employee;
    }

    public function canManageStockOperations(): bool
    {
        return $this !== self::Employee;
    }

    public function canViewInventoryReports(): bool
    {
        return $this !== self::Employee;
    }
}
