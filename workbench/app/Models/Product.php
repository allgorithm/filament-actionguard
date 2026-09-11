<?php

namespace Workbench\App\Models;

use Allgorithm\FilamentActionGuard\Checks\ConditionCheck;
use Allgorithm\FilamentActionGuard\Checks\MediaCheck;
use Allgorithm\FilamentActionGuard\Checks\NotEmptyCheck;
use Allgorithm\FilamentActionGuard\Checks\RequiredFieldCheck;
use Allgorithm\FilamentActionGuard\Traits\HasActionGuards;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasActionGuards;

    /**
     * ActionGuards to enforce when the product is in a specific state.
     */
    public function actionGuards(): array
    {
        return [
            'published' => [
                RequiredFieldCheck::make('name')->label('Produktname'),
                RequiredFieldCheck::make('sku')->label('Artikelnummer (SKU)'),
                NotEmptyCheck::make('price')->label('Verkaufspreis'),
                MediaCheck::make('image_url')->label('Produktbild'),
                ConditionCheck::make('valid_price', fn (Product $p) => ((float) $p->price) > 0, 'Preis muss größer als 0,00 € sein')->label('Mindestpreis'),
            ],
        ];
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'sku',
        'price',
        'status',
        'image_url',
        'category_id',
        'description',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Category, Product>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function publish(): bool
    {
        $this->status = 'published';

        return $this->save();
    }
}
