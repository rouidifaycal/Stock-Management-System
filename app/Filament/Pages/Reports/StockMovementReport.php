<?php

namespace App\Filament\Pages\Reports;

use App\Services\InventoryReportService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class StockMovementReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'reports/stock-movements';

    protected static ?string $title = 'Stock movement report';

    protected static ?string $navigationLabel = 'Stock movements';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

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
            ->query(fn (): Builder => $this->reportService->stockMovements())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date and time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Movement')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'entry' ? 'Stock entry' : 'Stock exit')
                    ->color(fn (string $state): string => $state === 'entry' ? 'success' : 'warning')
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->suffix(' units')
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Recorded by')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('movement_date')
                    ->label('Date range')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('To')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['from'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate('stock_movements.created_at', '>=', $data['from']),
                        )
                        ->when(
                            filled($data['until'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate('stock_movements.created_at', '<=', $data['until']),
                        )),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable(),
                SelectFilter::make('category')
                    ->relationship('product.category', 'name')
                    ->searchable(),
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable(),
                SelectFilter::make('type')
                    ->label('Movement type')
                    ->options([
                        'entry' => 'Stock entry',
                        'exit' => 'Stock exit',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No stock movements found')
            ->toolbarActions([]);
    }
}
