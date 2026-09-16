<p align="center">
    <img src="https://raw.githubusercontent.com/allgorithm/filament-actionguard/main/art/banner.png" alt="Filament ActionGuard" width="100%">
</p>

<p align="center">
    <a href="https://packagist.org/packages/allgorithm/filament-actionguard"><img src="https://img.shields.io/packagist/v/allgorithm/filament-actionguard.svg?style=flat-square&color=0ea5e9" alt="Latest Version on Packagist"></a>
    <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.3%20--%208.5-777BB4.svg?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3 - 8.5"></a>
    <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-v5.x-FDAE4B.svg?style=flat-square&logo=laravel&logoColor=white" alt="Filament v5"></a>
    <a href="https://pestphp.com"><img src="https://img.shields.io/badge/Pest-133%20Tests%20Passing-10b981.svg?style=flat-square&logo=pest" alt="Pest Tests"></a>
    <a href="https://phpstan.org"><img src="https://img.shields.io/badge/PHPStan-Level%208%20(0%20errors)-6366f1.svg?style=flat-square" alt="PHPStan Level 8"></a>
    <a href="https://plumbphp.dev/allgorithm/filament-actionguard"><img src="https://plumbphp.dev/badges/allgorithm/filament-actionguard/composite.svg" alt="Plumb score"></a>
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
- 💎 **Bulletproof Quality:** PHPStan **Level 8** (0 errors), 100% PSR-12 code style, and 133 comprehensive Pest tests.

## Compatibility

| ActionGuard | PHP | Laravel | Filament |
| :--- | :--- | :--- | :--- |
| `1.x` | `8.3–8.5` | `11.28–13.x` (through Filament) | `5.x` |

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

Bypasses are disabled by default. Enable `ACTIONGUARD_ALLOW_BYPASS=true` only
for a controlled maintenance operation; the bypass is scoped to that callback
and can emit a data-minimised audit event.

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
| `RelationshipCheck` | Eloquent relationship resolves to a model or non-empty collection; it may lazy-load the relation | `RelationshipCheck::make('category')` |
| `MediaCheck` | Non-empty Spatie MediaLibrary collection, string attribute, or array attribute | `MediaCheck::make('image_url')` |
| `CallbackCheck` | Evaluates custom logic that returns a `CheckResult` | `CallbackCheck::make('vat_id')->check(...)` |

`CallbackCheck` callbacks must return a `CheckResult` explicitly:

```php
use Allgorithm\FilamentActionGuard\Checks\CallbackCheck;
use Allgorithm\FilamentActionGuard\Results\CheckResult;

CallbackCheck::make('vat_id')->check(function ($record): CheckResult {
    return filled($record->vat_id)
        ? CheckResult::pass('vat_id', 'VAT ID')
        : CheckResult::fail('vat_id', 'VAT ID', 'A VAT ID is required.');
});
```

---

## ⚙️ Secure defaults & optional configuration

ActionGuard works with secure defaults without publishing a configuration file.
To review or override its operational safeguards, publish the package config:

```bash
php artisan vendor:publish --tag="filament-actionguard-config"
```

You can customize the modal appearance, heading, and width directly on the
action using Filament's fluent API:

```php
ActionGuardAction::make('publish')
    ->modalHeading('Verify Record Invariants')
    ->modalWidth('xl') // 'sm', 'md', 'lg', 'xl', '2xl'
    ->checks([...]);
```

The operational defaults and environment variables are:

| Setting | Environment variable | Default |
| :--- | :--- | :---: |
| `enabled` | `ACTIONGUARD_ENABLED` | `true` |
| `fail_closed` | `ACTIONGUARD_FAIL_CLOSED` | `true` |
| `allow_bypass` | `ACTIONGUARD_ALLOW_BYPASS` | `false` |
| `notifications` | `ACTIONGUARD_NOTIFICATIONS_ENABLED` | `true` |
| `audit.enabled` | `ACTIONGUARD_AUDIT_TRAIL` | `false` |
| `audit.channel` | `ACTIONGUARD_AUDIT_CHANNEL` | `null` |
| `audit.include_model_type` | `ACTIONGUARD_AUDIT_INCLUDE_MODEL_TYPE` | `false` |
| `audit.include_state` | `ACTIONGUARD_AUDIT_INCLUDE_STATE` | `false` |
| `allow_insecure_resolution_urls` | `ACTIONGUARD_ALLOW_INSECURE_RESOLUTION_URLS` | `false` |

Resolution links accept local absolute paths and HTTPS URLs. HTTP is available
only through its explicit compatibility switch; executable, protocol-relative,
malformed, and control-character URLs are discarded.

ActionGuard evaluates checks when the modal is rendered, when confirmation is
prepared, and immediately before execution. Checks must therefore be
side-effect-free and safe to run repeatedly. The final evaluation is decisive.

---

## 🧪 Testing & Quality Assurance

ActionGuard is built with strict quality standards:

```bash
# Run automated test suite (133 tests, 315 assertions)
composer test

# Run code style fixer and static analysis (PHPStan Level 8)
composer lint
```

## Production safeguards

ActionGuard is an invariant check, not an authorization, tenancy, or database
constraint system. Enforce authorization with Laravel policies and ensure that
critical bulk writes do not use `Model::where(...)->update()`, because Eloquent
does not dispatch model events for mass updates. For invariants that must hold
against every write path, add database constraints or enforce writes through an
application service. In production, keep `ACTIONGUARD_FAIL_CLOSED=true`, use a
unique `APP_KEY`, disable debug mode, enable secure cookies for HTTPS, and route
the optional data-minimised audit channel to your central logging system.

---

## Privacy / Datenschutz

Filament ActionGuard was designed with data minimisation in mind. The package
does not transmit record data to external services and its optional audit events
exclude model types, state values, record IDs, actor IDs, check messages, and
arbitrary model attributes by default. Model type and state can be enabled
independently when an application's documented audit purpose requires them.

Whether a deployment complies with the GDPR / DSGVO depends on the operator's
specific processing purposes, legal basis, access controls, logging and
retention settings, hosting, privacy notice, and any required data-processing
agreements. The operator remains responsible for assessing and documenting
compliant use.

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

The project artwork was generated with AI; see [artwork attribution](art/ATTRIBUTION.md).

---

<p align="center">
    Designed with ❤️ by <a href="https://github.com/allgorithm"><strong>Allgorithm</strong></a>
</p>
