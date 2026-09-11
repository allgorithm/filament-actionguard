<?php

namespace Allgorithm\FilamentActionGuard\Tests;

use Allgorithm\FilamentActionGuard\ActionGuardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ActionGuardServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:mwI8+tv+nFBfp+UiEtskdA471cijww4UZEwfIW/znbQ=');
    }
}
