<?php

use Allgorithm\FilamentActionGuard\Support\ActionGuardAudit;
use Illuminate\Support\Facades\Log;

it('does not write audit records unless auditing is explicitly enabled', function () {
    config()->set('filament-actionguard.audit.enabled', false);
    Log::spy();

    ActionGuardAudit::record('invariant_blocked', ['model' => 'Example']);

    Log::shouldNotHaveReceived('channel');
});

it('writes only the supplied structured audit context to the configured channel', function () {
    config()->set('filament-actionguard.audit.enabled', true);
    config()->set('filament-actionguard.audit.channel', 'stack');
    Log::shouldReceive('channel')->once()->with('stack')->andReturnSelf();
    Log::shouldReceive('notice')->once()->with('filament-actionguard.invariant_blocked', [
        'model' => 'Example',
        'state' => 'published',
        'failed' => 1,
        'errors' => 0,
    ]);

    ActionGuardAudit::record('invariant_blocked', [
        'model' => 'Example',
        'state' => 'published',
        'failed' => 1,
        'errors' => 0,
    ]);
});
