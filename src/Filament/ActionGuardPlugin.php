<?php

namespace Allgorithm\FilamentActionGuard\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ActionGuardPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-actionguard';
    }

    public function register(Panel $panel): void
    {
        // Register panel specific configurations if needed
    }

    public function boot(Panel $panel): void
    {
        // Boot panel specific configurations if needed
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
