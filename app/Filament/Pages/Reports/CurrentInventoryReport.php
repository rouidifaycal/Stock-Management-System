<?php

namespace App\Filament\Pages\Reports;

use App\Models\Product;
use App\Services\InventoryReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class CurrentInventoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'reports/current-inventory';

    protected static ?string $title = 'Current inventory report';

    protected static ?string $navigationLabel = 'Current inventory';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected InventoryReportService $reportService;

    public function boot(InventoryReportService $reportService): void
    {
        $this->reportService = $reportService;
    }

    public static function canAccess(): bool
    {
        return Gate::allows('viewInventoryReports');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->reportService->inventory())
            ->columns([
                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Unit price')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Current stock')
                    ->suffix(' units')
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Minimum stock')
                    ->suffix(' units')
                    ->sortable(),
                TextColumn::make('inventory_value')
                    ->label('Inventory value')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('stock_status')
                    ->label('Status')
                    ->state(fn (Product $record): string => match (true) {
                        $record->quantity === 0 => 'Out of stock',
                        $record->quantity <= $record->min_stock => 'Low stock',
                        default => 'Normal',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Out of stock' => 'danger',
                        'Low stock' => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable(),
                SelectFilter::make('stock_status')
                    ->label('Stock status')
                    ->options([
                        'normal' => 'Normal',
                        'low' => 'Low stock',
                        'out_of_stock' => 'Out of stock',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'normal' => $query->whereColumn('quantity', '>', 'min_stock'),
                        'low' => $query
                            ->where('quantity', '>', 0)
                            ->whereColumn('quantity', '<=', 'min_stock'),
                        'out_of_stock' => $query->where('quantity', 0),
                        default => $query,
                    }),
            ])
            ->defaultSort('name')
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No inventory records found')
            ->toolbarActions([]);
    }
}
