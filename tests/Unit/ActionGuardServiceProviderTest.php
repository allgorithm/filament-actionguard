<?php

use Allgorithm\FilamentActionGuard\ActionGuardServiceProvider;
use Illuminate\Support\ServiceProvider;

it('registers publishable config views and translations', function () {
    $configPaths = ServiceProvider::pathsToPublish(
        ActionGuardServiceProvider::class,
        'filament-actionguard-config',
    );
    $viewPaths = ServiceProvider::pathsToPublish(
        ActionGuardServiceProvider::class,
        'filament-actionguard-views',
    );
    $translationPaths = ServiceProvider::pathsToPublish(
        ActionGuardServiceProvider::class,
        'filament-actionguard-translations',
    );

    expect($configPaths)->toHaveCount(1)
        ->and(array_key_first($configPaths))->toEndWith('/config/filament-actionguard.php')
        ->and($viewPaths)->toHaveCount(1)
        ->and(array_key_first($viewPaths))->toEndWith('/resources/views')
        ->and($translationPaths)->toHaveCount(1)
        ->and(array_key_first($translationPaths))->toEndWith('/resources/lang');
});
