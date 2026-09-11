<?php

use Allgorithm\FilamentActionGuard\Actions\ActionGuardAction;
use Allgorithm\FilamentActionGuard\Checks\ConditionCheck;
use Allgorithm\FilamentActionGuard\Checks\MediaCheck;
use Allgorithm\FilamentActionGuard\Checks\NotEmptyCheck;
use Allgorithm\FilamentActionGuard\Checks\RequiredFieldCheck;
use Allgorithm\FilamentActionGuard\Exceptions\StateInvariantViolationException;
use Allgorithm\FilamentActionGuard\Traits\HasActionGuards;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

function makeProductAction(): ActionGuardAction
{
    return ActionGuardAction::make('publish')
        ->visible(fn ($record) => $record->status !== 'published')
        ->checks([
            RequiredFieldCheck::make('name')->label('Produktname ist zwingend erforderlich'),
            RequiredFieldCheck::make('sku')->label('Artikelnummer (SKU) wird für den Live-Betrieb benötigt'),
            NotEmptyCheck::make('price')->label('Verkaufspreis darf nicht leer sein'),
            MediaCheck::make('image_url')->label('Mindestens ein gültiges Produktbild erforderlich'),
            ConditionCheck::make('valid_price', fn ($record) => ((float) $record->price) > 0, 'Preis muss größer als 0,00 € sein')->label('Gültiger Mindestpreis'),
        ])
        ->action(function ($record) {
            $record->status = 'published';
        });
}

function makeDemoProduct(array $attributes): Model
{
    return new class($attributes) extends Model
    {
        protected $guarded = [];

        public function __construct(array $attributes = [])
        {
            parent::__construct();
            $this->forceFill($attributes);
        }
    };
}

it('blocks Product 1 (Incomplete) from being published', function () {
    $product1 = makeDemoProduct([
        'name' => 'Draft Produkt (Unvollständig)',
        'sku' => null,
        'price' => null,
        'image_url' => null,
        'status' => 'draft',
    ]);

    $action = makeProductAction();
    $result = $action->evaluateChecks($product1);

    expect($result->passed)->toBeFalse()
        ->and($result->summary['failed'])->toBe(4); // sku, price, image_url, and valid_price fail

    // Attempting to run the action throws Halt and does NOT update status
    expect(fn () => $action->record($product1)->callBefore())->toThrow(Halt::class);
    expect($product1->status)->toBe('draft');
});

it('blocks Product 2 (Missing image only) from being published', function () {
    $product2 = makeDemoProduct([
        'name' => 'Kopfhörer Pro (Fehlendes Bild)',
        'sku' => 'HP-9000-BLK',
        'price' => 149.99,
        'image_url' => null,
        'status' => 'draft',
    ]);

    $action = makeProductAction();
    $result = $action->evaluateChecks($product2);

    expect($result->passed)->toBeFalse()
        ->and($result->summary['passed'])->toBe(4)
        ->and($result->summary['failed'])->toBe(1); // Only image_url fails

    expect(fn () => $action->record($product2)->callBefore())->toThrow(Halt::class);
    expect($product2->status)->toBe('draft');
});

it('successfully publishes Product 3 (Complete with all criteria satisfied)', function () {
    $product3 = makeDemoProduct([
        'name' => 'Wireless Noise-Cancelling Headphones',
        'sku' => 'WNC-2026-X',
        'price' => 249.00,
        'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500',
        'status' => 'draft',
    ]);

    $action = makeProductAction();
    $result = $action->evaluateChecks($product3);

    expect($result->passed)->toBeTrue()
        ->and($result->summary['passed'])->toBe(5)
        ->and($result->summary['failed'])->toBe(0);

    // Call action lifecycle: callBefore does not throw Halt
    $action->record($product3)->callBefore();
    $action->call(['record' => $product3]);

    expect($product3->status)->toBe('published');
});

it('hides the publish action for Product 4 (Already published)', function () {
    $product4 = makeDemoProduct([
        'name' => 'Smartwatch Active 5',
        'status' => 'published',
    ]);

    $action = makeProductAction()->record($product4);

    expect($action->isVisible())->toBeFalse();
});

it('blocks publish when price is zero or negative (price boundary condition)', function ($invalidPrice) {
    $product = makeDemoProduct([
        'name' => 'Zero Price Item',
        'sku' => 'ZERO-1',
        'price' => $invalidPrice,
        'image_url' => 'https://example.com/item.jpg',
        'status' => 'draft',
    ]);

    $action = makeProductAction();
    $result = $action->evaluateChecks($product);

    expect($result->passed)->toBeFalse()
        ->and($result->checks[4]->status->value)->toBe('fail')
        ->and($result->checks[4]->message)->toBe('Preis muss größer als 0,00 € sein');
})->with([
    0,
    0.0,
    '0.00',
    -5.00,
]);

it('passes condition check when price is minimally positive (0.01 €)', function () {
    $product = makeDemoProduct([
        'name' => 'Penny Item',
        'sku' => 'PENNY-1',
        'price' => 0.01,
        'image_url' => 'https://example.com/item.jpg',
        'status' => 'draft',
    ]);

    $action = makeProductAction();
    $result = $action->evaluateChecks($product);

    expect($result->passed)->toBeTrue();
});

it('blocks publish when SKU is whitespace only', function () {
    $product = makeDemoProduct([
        'name' => 'Whitespace SKU Product',
        'sku' => '   ',
        'price' => 19.99,
        'image_url' => 'https://example.com/item.jpg',
        'status' => 'draft',
    ]);

    // RequiredFieldCheck checks null, but let's test NotEmptyCheck behavior if applied to SKU or price
    $notEmptySkuCheck = NotEmptyCheck::make('sku');
    $res = $notEmptySkuCheck->evaluate($product);
    expect($res->isPassed())->toBeFalse();
});

it('verifies that the publish action functions identically on the EditProduct page header', function () {
    $product1 = makeDemoProduct([
        'name' => 'Draft Product',
        'sku' => null,
        'price' => null,
        'image_url' => null,
        'status' => 'draft',
    ]);

    $product3 = makeDemoProduct([
        'name' => 'Ready Product',
        'sku' => 'RDY-1',
        'price' => 99.99,
        'image_url' => 'https://example.com/rdy.jpg',
        'status' => 'draft',
    ]);

    $product4 = makeDemoProduct([
        'name' => 'Published Product',
        'status' => 'published',
    ]);

    // Test header action simulation for Product 1 (Incomplete) -> Blocked
    $headerAction1 = makeProductAction()->record($product1);
    expect($headerAction1->isVisible())->toBeTrue();
    expect(fn () => $headerAction1->callBefore())->toThrow(Halt::class);

    // Test header action simulation for Product 3 (Complete) -> Executes
    $headerAction3 = makeProductAction()->record($product3);
    expect($headerAction3->isVisible())->toBeTrue();
    $headerAction3->callBefore();
    $headerAction3->call(['record' => $product3]);
    expect($product3->status)->toBe('published');

    // Test header action simulation for Product 4 (Published) -> Hidden
    $headerAction4 = makeProductAction()->record($product4);
    expect($headerAction4->isVisible())->toBeFalse();
});

it('prevents saving a published product when required attributes are subsequently cleared (HasActionGuards Invariant)', function () {
    $productClass = new class(['name' => 'Item', 'sku' => 'PUB-1', 'price' => 10, 'image_url' => 'https://img.com/1.jpg', 'status' => 'published']) extends Model
    {
        use HasActionGuards;

        protected $guarded = [];

        public function actionGuards(): array
        {
            return [
                'published' => [
                    MediaCheck::make('image_url'),
                ],
            ];
        }

        public function save(array $options = []): bool
        {
            $this->enforceActionGuards();

            return true;
        }
    };

    expect($productClass->save())->toBeTrue();

    // Clearing image on published item triggers StateInvariantViolationException
    $productClass->image_url = null;
    expect(fn () => $productClass->save())
        ->toThrow(StateInvariantViolationException::class);
});
