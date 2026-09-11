# Changelog

All notable changes to `filament-actionguard` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-09-11

### Added

- **Two-Phase Invariant Defense Architecture**
  - **Phase 1: Preflight Interception (`ActionGuardAction`)**: Interactive modal interception before action execution, validating state transitions and business constraints.
  - **Phase 2: Model Invariant Enforcement (`HasActionGuards`)**: Eloquent model lifecycle hooks (`saving`) preventing invalid state transitions and data regression on direct database saves.
  - `forState()` binding: Automatic discovery and wiring of model invariants into UI actions from a single source of truth.
  - `withoutActionGuards()` bypass callback for administrative tasks, seeders, and bulk imports.
  - `StateInvariantViolationException`: Fail-closed exception containing structured failure messages mapped to form fields.

- **6 Built-in Standard Inspection Checks**
  - `RequiredFieldCheck`: Validates presence and non-null status of critical model attributes.
  - `NotEmptyCheck`: Validates strings, arrays, and collections with precise numeric zero (`0`, `'0'`, `0.00`) preservation.
  - `ConditionCheck`: Custom boolean and closure-based rule evaluations with contextual failure messages.
  - `RelationshipCheck`: Ensures required relationships exist and meet minimum count constraints.
  - `MediaCheck`: Verifies attachments via Spatie MediaLibrary collections or standard URL/path attributes.
  - `CallbackCheck`: Flexible closure-driven check returning custom `CheckResult` instances with explicit severity and descriptions.

- **Filament 5 & UI Integration**
  - `ActionGuardPlugin`: Seamless Filament panel plugin registration under the ID `filament-actionguard`.
  - Interactive preflight modal (`preflight-modal.blade.php`) with real-time visual status badges (Passed / Failed / Error), severity tags, and details.
  - Filament notification dispatcher: Danger notifications providing immediate actionable feedback upon failed criteria.
  - Full bilingual localization (i18n): Complete English (`en`) and German (`de`) translation dictionaries for UI and check descriptions.

- **Enterprise Core Bridge**
  - `BusinessCoreGuardAdapter`: Bridges enterprise domain operation descriptors into ActionGuard check contracts.
  - `EnterpriseBridgeResolver`: Auto-resolves operation guard descriptors and adapts enterprise domain rules.
  - Standalone Community Fallback: Gracefully operates independently when `allgorithm/business-core` is absent.
  - Fail-closed runtime safety: Catches unexpected check/adapter exceptions and blocks execution safely.
  - Strict BusinessCore v1.2 contract detection without a hard Community dependency.
  - Automatic typed `ActorContext` and `OperationContext` construction after licensed Core installation.
  - Replaceable `OperationContextFactoryContract` for zero-code-change BusinessCore identity and tenancy integration.
  - Correlation-based, localized failure messages without exposing internal exception details in the Filament UI.
  - Safe structured resolution rendering with URL allow-listing, escaped labels, and reverse-tabnabbing protection.

- **Enterprise CI/CD & Quality Assurance**
  - Multi-version GitHub Actions test matrix (`run-tests.yml`) validating across PHP 8.2, 8.3, and 8.4 on `ubuntu-latest`.
  - Automated PHPStan static analysis workflow (`phpstan.yml`) enforcing **Level 8** compliance.
  - Automated Laravel Pint code style workflow (`fix-style.yml`) ensuring 100% PSR-12 standard compliance.
  - Dependabot automated weekly dependency monitoring (`dependabot.yml`) for Composer and GitHub Actions.
  - Community health and contribution standards: `SECURITY.md`, `CONTRIBUTING.md`, `LICENSE.md`, and structured GitHub issue/PR templates.

- **Filament Marketplace Compliance**
  - Marketplace visual assets: 16:9 banner (`art/banner.png`, 2560x1440) and author avatar (`art/avatar.png`, 1000x1000).
  - Composer metadata configured with `filament-plugin` type, plugin ID, homepage, authors, and issue tracker links.
  - Streamlined `.gitattributes` with `export-ignore` rules for lean Packagist distribution archives.

- **Developer Experience & Tooling**
  - Root `artisan` CLI bridge to Orchestra Testbench and Workbench environment.
  - Comprehensive test suite with 98 Pest tests and 225 assertions covering architecture, unit checks, traits, enterprise bridge hardening, and demo product scenarios.

[1.0.0]: https://github.com/allgorithm/filament-actionguard/releases/tag/v1.0.0
