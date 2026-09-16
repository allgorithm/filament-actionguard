# Contributing to Filament ActionGuard

Thank you for considering contributing to Filament ActionGuard!

## Code of Conduct

Please be respectful, helpful, and collaborative in all interactions across issues, pull requests, and discussions.

## Development Workflow

### Setup

Clone the repository and install dependencies:

```bash
git clone https://github.com/allgorithm/filament-actionguard.git
cd filament-actionguard
composer update
```

As this repository contains a reusable library, `composer.lock` is generated
locally but is not committed. CI resolves the supported dependency ranges on
every run, including a `prefer-lowest` test job.

### Running Tests

We use [Pest](https://pestphp.com) for testing:

```bash
composer test
# or directly
vendor/bin/pest
```

### Static Analysis

We enforce **PHPStan Level 8**:

```bash
vendor/bin/phpstan analyse --verbose --ansi
```

### Code Styling

We use [Laravel Pint](https://laravel.com/docs/pint) for code formatting:

```bash
# Check code style
vendor/bin/pint --test

# Fix code style automatically
vendor/bin/pint
```

Or run all linting checks:

```bash
composer lint
```

### Local Testbench / Workbench

You can start the workbench dev server to test the plugin interactively:

```bash
composer serve
```

## Pull Request Guidelines

1. Fork the repository and create your feature branch from `main`.
2. Ensure all tests pass (`composer test`).
3. Ensure PHPStan Level 8 passes with 0 errors.
4. Ensure code formatting conforms to Pint standards (`composer lint`).
5. Write clear, concise commit messages.
6. Submit a pull request referencing any relevant issue numbers.
