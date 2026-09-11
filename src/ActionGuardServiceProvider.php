<?php

namespace Allgorithm\FilamentActionGuard;

use Allgorithm\FilamentActionGuard\Bridge\DefaultOperationContextFactory;
use Allgorithm\FilamentActionGuard\Contracts\OperationContextFactoryContract;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ActionGuardServiceProvider extends PackageServiceProvider
{
    public function packageRegistered(): void
    {
        $this->app->bindIf(
            OperationContextFactoryContract::class,
            DefaultOperationContextFactory::class,
        );
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('filament-actionguard')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews();
    }
}
