<?php

namespace Workbench\App\Filament\Resources;

use Allgorithm\FilamentActionGuard\Actions\ActionGuardAction;
use Allgorithm\FilamentActionGuard\Checks\ConditionCheck;
use Allgorithm\FilamentActionGuard\Checks\MediaCheck;
use Allgorithm\FilamentActionGuard\Checks\NotEmptyCheck;
use Allgorithm\FilamentActionGuard\Checks\RequiredFieldCheck;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\ProductResource\Pages\CreateProduct;
use Workbench\App\Filament\Resources\ProductResource\Pages\EditProduct;
use Workbench\App\Filament\Resources\ProductResource\Pages\ListProducts;
use Workbench\App\Models\Product;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Produkte (Demo)';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Produktname')
                    ->required(),
                TextInput::make('sku')
                    ->label('Artikelnummer (SKU)'),
                TextInput::make('price')
                    ->label('Preis (€)')
                    ->numeric(),
                TextInput::make('image_url')
                    ->label('Produktbild (URL)'),
                Select::make('category_id')
                    ->label('Kategorie')
                    ->relationship('category', 'name'),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Entwurf (Draft)',
                        'published' => 'Veröffentlicht (Published)',
                    ])
                    ->default('draft')
                    ->disabled()
                    ->helperText('Der Status kann nicht manuell editiert werden, sondern wird über ActionGuard („Veröffentlichen“) gesteuert.')
                    ->required(),
                Textarea::make('description')
                    ->label('Beschreibung')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Produktname')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable(),
                ImageColumn::make('image_url')
                    ->label('Bild')
                    ->circular(),
                TextColumn::make('category.name')
                    ->label('Kategorie'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->actions([
                static::getPublishAction(),
                EditAction::make(),
            ]);
    }

    public static function getPublishAction(): ActionGuardAction
    {
        return ActionGuardAction::make('publish')
            ->label('Veröffentlichen')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->visible(fn (Product $record) => $record->status !== 'published')
            ->checks([
                RequiredFieldCheck::make('name')->label('Produktname ist zwingend erforderlich'),
                RequiredFieldCheck::make('sku')->label('Artikelnummer (SKU) wird für den Live-Betrieb benötigt'),
                NotEmptyCheck::make('price')->label('Verkaufspreis darf nicht leer sein'),
                MediaCheck::make('image_url')->label('Mindestens ein gültiges Produktbild erforderlich'),
                ConditionCheck::make('valid_price', fn (Product $record) => ((float) $record->price) > 10, 'Preis muss größer als 0,00 € sein')->label('Gültiger Mindestpreis'),
            ])
            ->action(function (Product $record) {
                $record->publish();
                Notification::make()
                    ->title('Produkt erfolgreich freigegeben und veröffentlicht!')
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
