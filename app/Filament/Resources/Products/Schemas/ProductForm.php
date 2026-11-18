<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sku')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(99999999.99)
                    ->step(0.01),
                TextInput::make('quantity')
                    ->label('Current stock')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Stock levels are managed separately from the product catalog.'),
                TextInput::make('min_stock')
                    ->label('Minimum stock')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(4294967295)
                    ->default(0),
                FileUpload::make('image')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->disk('public')
                    ->directory('products')
                    ->visibility('public')
                    ->deleteUploadedFileUsing(function (string|TemporaryUploadedFile $file): void {
                        if (is_string($file)) {
                            Storage::disk('public')->delete($file);
                        }
                    }),
            ]);
    }
}
