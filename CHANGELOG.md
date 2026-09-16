# Changelog

All notable changes to `filament-actionguard` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The library no longer commits `composer.lock`; CI resolves dependencies from
  the declared constraints for every run and audits the generated lock file.

### Security

- Pinned all third-party GitHub Actions to immutable full commit SHAs.
- Added Dependabot cooldowns of at least three days for Composer and GitHub
  Actions updates to reduce exposure to newly compromised releases.
- Restricted GitHub Actions tokens to read-only repository contents and stopped
  checkout credentials from persisting in the job workspace.

## [1.3.0] - 2026-09-14

### Added

- Publishable runtime configuration for enablement, fail-closed behavior,
  notifications, guarded bypasses, audit logging, and resolution URL policy.
- Data-minimised structured audit events for action evaluation, invariant
  blocking, and explicitly enabled bypass usage.
- Independent, disabled-by-default audit opt-ins for model types and state values;
  unknown context fields are removed by a central event allow-list.
- Locked and prefer-lowest dependency verification in CI.
- Privacy responsibility and AI-generated artwork disclosures.
- Central resolution URL sanitization shared by Community and Enterprise checks.
- Filament Livewire integration coverage for table actions, page header actions,
  modal halting, model validation errors, and notification configuration.

### Changed

- Composer now resolves stable dependencies by default and locks against the
  minimum supported PHP 8.3 platform.
- ActionGuard bypasses are disabled by default and scoped to their callback.
- Resolution links allow HTTPS and relative URLs by default; HTTP requires an
  explicit compatibility opt-in.
- Security policy now documents supported versions and response expectations.
- The local environment example now contains only Workbench and ActionGuard
  settings and keeps audit logging disabled by default.

### Security

- Check exceptions are reported server-side with correlation IDs instead of
  exposing internal exception messages in the Filament interface.
- Explicit checks are validated against `ActionGuardCheckContract`, and rich
  action labels are stripped of HTML before use as modal headings.
- Direct `CheckResolution` URLs now use the same scheme allow-list and malformed
  URL rejection as Enterprise resolutions.

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
  - `RelationshipCheck`: Ensures required relationships resolve to a model or non-empty collection.
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
  - Multi-version GitHub Actions test matrix (`run-tests.yml`) validating across PHP 8.3, 8.4, and 8.5 on `ubuntu-latest`.
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
  - Comprehensive test suite with 133 Pest tests and 315 assertions covering architecture, unit checks, traits, enterprise bridge hardening, Livewire integration, security, and demo product scenarios.

[1.0.0]: https://github.com/allgorithm/filament-actionguard/releases/tag/v1.0.0
[1.3.0]: https://github.com/allgorithm/filament-actionguard/releases/tag/v1.3.0
[Unreleased]: https://github.com/allgorithm/filament-actionguard/compare/v1.3.0...HEAD
