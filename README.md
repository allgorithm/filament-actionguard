<p align="center">
    <img src="https://raw.githubusercontent.com/allgorithm/filament-actionguard/main/art/banner.png" alt="Filament ActionGuard" width="100%">
</p>

<p align="center">
    <a href="https://packagist.org/packages/allgorithm/filament-actionguard"><img src="https://img.shields.io/packagist/v/allgorithm/filament-actionguard.svg?style=flat-square&color=0ea5e9" alt="Latest Version on Packagist"></a>
    <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.3%20--%208.5-777BB4.svg?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3 - 8.5"></a>
    <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-v5.x-FDAE4B.svg?style=flat-square&logo=laravel&logoColor=white" alt="Filament v5"></a>
    <a href="https://pestphp.com"><img src="https://img.shields.io/badge/Pest-98%20Tests%20Passing-10b981.svg?style=flat-square&logo=pest" alt="Pest Tests"></a>
    <a href="https://phpstan.org"><img src="https://img.shields.io/badge/PHPStan-Level%208%20(0%20errors)-6366f1.svg?style=flat-square" alt="PHPStan Level 8"></a>
    <a href="LICENSE.md"><img src="https://img.shields.io/badge/License-MIT-gray.svg?style=flat-square" alt="License MIT"></a>
</p>

---

# Filament ActionGuard

> **🛡️ Stop incomplete records and prevent data degradation across critical Filament actions.**

**Filament ActionGuard** is a production-grade preflight gatekeeper and state invariant defense plugin for **Filament**. It prevents incomplete, invalid, or corrupted records from being published, approved, or transitioned into critical lifecycle states.

---

## 🛡️ The Two-Phase Invariant Defense System

Standard form validation only validates input fields during form submissions. It **cannot** protect your business from missing relationships, empty media collections, external API dependencies, or state regressions caused by direct model updates.

ActionGuard introduces a robust **Two-Phase Invariant Defense System**:

```
┌─────────────────────────────────────────────────────────────────────────┐
│ PHASE 1: PREFLIGHT MODAL (ActionGuardAction)                            │
│ Interactive UI confirmation dialog BEFORE an action executes            │
│ • Evaluates all configured criteria against the current record          │
│ • Displays an intuitive visual checklist (Passed / Failed / Error)      │
│ • Blocks action execution if any required invariant fails (Fail-Closed) │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │
                                     ▼ (Action Executes)
┌─────────────────────────────────────────────────────────────────────────┐
│ PHASE 2: MODEL INVARIANT PROTECTION (HasActionGuards Trait)             │
│ Automatic Eloquent hook ON SAVE / UPDATE                                │
│ • Prevents records in a protected state from degrading into an invalid  │
│   state (e.g. deleting images or SKU on an already published product)   │
│ • Dispatches Filament danger notifications with exact failure details   │
│ • Throws StateInvariantViolationException mapped to form fields         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## ✨ Features

- 🚀 **Preflight Action Modal:** Wrap any Filament table or page action into an `ActionGuardAction` with instant visual breakdown.
- 🔒 **Model Invariant Enforcement (`HasActionGuards`):** Define business rules once on the model; protect records against corrupting saves and updates.
- 🎯 **Single Source of Truth (`forState()`):** Automatically reuse model guards inside table/page actions without code duplication.
- 🧰 **6 Battle-Tested Standard Checks:**
  - `RequiredFieldCheck`: Validates presence of critical record attributes.
  - `NotEmptyCheck`: Handles strings, arrays, zero-values (`0`, `'0'`, `0.00`), and collections accurately.
  - `ConditionCheck`: Custom boolean callback closures with descriptive error messages.
  - `RelationshipCheck`: Verifies loaded relationships (e.g. belongs-to, has-many).
  - `MediaCheck`: Checks media collections (Spatie MediaLibrary or file upload paths).
  - `CallbackCheck`: Flexible check with full `CheckResult` control.
- 🌍 **Fully Localized (i18n):** Complete English and German translations included out-of-the-box.
- 💎 **Bulletproof Quality:** PHPStan **Level 8** (0 errors), 100% PSR-12 code style, and 98 comprehensive Pest tests.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require allgorithm/filament-actionguard
```

Optionally publish the view and translation files:

```bash
php artisan vendor:publish --tag="filament-actionguard-views"
php artisan vendor:publish --tag="filament-actionguard-translations"
```

---

## 🚀 Quickstart

### 1. Preflight Action on Filament Tables / Pages

Replace standard `Action::make()` with `ActionGuardAction::make()`:

```php
use Allgorithm\FilamentActionGuard\Actions\ActionGuardAction;
use Allgorithm\FilamentActionGuard\Checks\ConditionCheck;
use Allgorithm\FilamentActionGuard\Checks\MediaCheck;
use Allgorithm\FilamentActionGuard\Checks\NotEmptyCheck;
use Allgorithm\FilamentActionGuard\Checks\RelationshipCheck;
use Allgorithm\FilamentActionGuard\Checks\RequiredFieldCheck;

ActionGuardAction::make('publish')
    ->label('Publish Product')
    ->icon('heroicon-o-arrow-up-circle')
    ->color('success')
    ->visible(fn ($record) => $record->status !== 'published')
    ->checks([
        RequiredFieldCheck::make('name')
            ->label('Product Title'),

        RequiredFieldCheck::make('sku')
            ->label('SKU / EAN Code'),

        NotEmptyCheck::make('price')
            ->label('Selling Price'),

        ConditionCheck::make('valid_price', fn ($record) => (float) $record->price > 0, 'Price must be greater than 0,00 €')
            ->label('Valid Minimum Price'),

        MediaCheck::make('images')
            ->label('Gallery Images'),

        RelationshipCheck::make('category')
            ->label('Assigned Category'),
    ])
    ->action(function ($record) {
        $record->update(['status' => 'published']);
        
        \Filament\Notifications\Notification::make()
            ->title('Product published successfully')
            ->success()
            ->send();
    });
```

---

### 2. Model Invariant Protection (`HasActionGuards`)

Protect models from invalid edits after they are in a protected lifecycle state (e.g. `published`, `active`, `approved`):

```php
namespace App\Models;

use Allgorithm\FilamentActionGuard\Checks\ConditionCheck;
use Allgorithm\FilamentActionGuard\Checks\MediaCheck;
use Allgorithm\FilamentActionGuard\Checks\NotEmptyCheck;
use Allgorithm\FilamentActionGuard\Checks\RequiredFieldCheck;
use Allgorithm\FilamentActionGuard\Traits\HasActionGuards;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasActionGuards;

    protected $guarded = [];

    /**
     * Define the ActionGuards for specific model states.
     */
    public function actionGuards(): array
    {
        return [
            'published' => [
                RequiredFieldCheck::make('name')->label('Product Name'),
                RequiredFieldCheck::make('sku')->label('SKU'),
                NotEmptyCheck::make('price')->label('Price'),
                ConditionCheck::make('valid_price', fn ($model) => ((float) $model->price) > 0, 'Price must be positive')
                    ->label('Valid Price'),
                MediaCheck::make('image_url')->label('Product Image'),
            ],
        ];
    }
}
```

Now, any attempt to save a published product that violates these guards will be blocked immediately, throwing a `StateInvariantViolationException` and notifying the user in the Filament UI.

#### Temporary Bypass (e.g., Data Migrations)
```php
Product::withoutActionGuards(function () use ($product) {
    $product->update(['sku' => 'TEMP-SKU']);
});
```

---

### 3. Single Source of Truth (`forState`)

Link the table action directly to your model's guard definitions without writing checks twice:

```php
ActionGuardAction::make('publish')
    ->label('Publish')
    ->forState('published') // Automatically resolves checks defined in Product::actionGuards()
    ->action(fn ($record) => $record->update(['status' => 'published']));
```

---

## 🗂️ Built-in Checks Reference

| Check Class | Evaluates | Example |
| :--- | :--- | :--- |
| `RequiredFieldCheck` | Attribute is not `null` | `RequiredFieldCheck::make('title')` |
| `NotEmptyCheck` | Value is not empty string/whitespace/empty array/empty collection (safely preserves `0`, `'0'`, `0.00`) | `NotEmptyCheck::make('price')` |
| `ConditionCheck` | Boolean callback closure | `ConditionCheck::make('min_stock', fn ($record) => $record->stock > 0, 'Stock must be greater than zero')` |
| `RelationshipCheck` | Eloquent relationship exists and is loaded | `RelationshipCheck::make('category')` |
| `MediaCheck` | Spatie MediaLibrary or array of image URLs | `MediaCheck::make('image_url')` |
| `CallbackCheck` | Evaluates custom logic returning `CheckResult` | `CallbackCheck::make('vat_id', fn ($record) => ...)` |

---

## ⚙️ Fluent Customization & Zero-Config

ActionGuard is designed as **Zero-Configuration by Default**. It requires no separate configuration file. You can customize the modal appearance, heading, and width directly on the action using Filament's fluent API:

```php
ActionGuardAction::make('publish')
    ->modalHeading('Verify Record Invariants')
    ->modalWidth('xl') // 'sm', 'md', 'lg', 'xl', '2xl'
    ->checks([...]);
```

---

## 🧪 Testing & Quality Assurance

ActionGuard is built with strict quality standards:

```bash
# Run automated test suite (98 tests, 225 assertions)
composer test

# Run code style fixer and static analysis (PHPStan Level 8)
composer lint
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

<p align="center">
    Designed with ❤️ by <a href="https://github.com/allgorithm"><strong>Allgorithm</strong></a>
</p>
