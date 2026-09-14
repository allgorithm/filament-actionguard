<?php

use Allgorithm\FilamentActionGuard\Support\ActionGuardAudit;
use Illuminate\Support\Facades\Log;

it('does not write audit records unless auditing is explicitly enabled', function () {
    config()->set('filament-actionguard.audit.enabled', false);
    Log::spy();

    ActionGuardAudit::record('invariant_blocked', ['model' => 'Example']);

    Log::shouldNotHaveReceived('channel');
});

it('allows only data-minimised event context by default', function () {
    config()->set('filament-actionguard.audit.enabled', true);
    config()->set('filament-actionguard.audit.channel', 'stack');
    Log::shouldReceive('channel')->times(3)->with('stack')->andReturnSelf();
    Log::shouldReceive('notice')->once()->with('filament-actionguard.invariant_blocked', [
        'failed' => 1,
        'errors' => 0,
    ]);
    Log::shouldReceive('notice')->once()->with('filament-actionguard.bypass_used', []);
    Log::shouldReceive('notice')->once()->with('filament-actionguard.custom_event', []);

    ActionGuardAudit::record('invariant_blocked', [
        'model' => 'Example',
        'state' => 'published',
        'failed' => 1,
        'errors' => 0,
        'record_id' => 42,
        'actor_id' => 'user-1',
        'message' => 'Sensitive failure details',
    ]);
    ActionGuardAudit::record('bypass_used', ['model' => 'Example']);
    ActionGuardAudit::record('custom_event', ['message' => 'Unregistered context']);
});

it('includes model type and state only through their independent opt-ins', function () {
    config()->set('filament-actionguard.audit.enabled', true);
    config()->set('filament-actionguard.audit.channel', 'stack');
    Log::shouldReceive('channel')->times(4)->with('stack')->andReturnSelf();
    Log::shouldReceive('notice')->once()->with('filament-actionguard.invariant_blocked', [
        'failed' => 1,
        'errors' => 0,
        'model' => 'Example',
    ]);
    Log::shouldReceive('notice')->once()->with('filament-actionguard.invariant_blocked', [
        'failed' => 1,
        'errors' => 0,
        'state' => 'published',
    ]);
    Log::shouldReceive('notice')->once()->with('filament-actionguard.invariant_blocked', [
        'failed' => 1,
        'errors' => 0,
        'model' => 'Example',
        'state' => 'published',
    ]);
    Log::shouldReceive('notice')->once()->with('filament-actionguard.bypass_used', [
        'model' => 'Example',
    ]);

    $context = [
        'model' => 'Example',
        'state' => 'published',
        'failed' => 1,
        'errors' => 0,
    ];

    config()->set('filament-actionguard.audit.include_model_type', true);
    ActionGuardAudit::record('invariant_blocked', $context);

    config()->set('filament-actionguard.audit.include_model_type', false);
    config()->set('filament-actionguard.audit.include_state', true);
    ActionGuardAudit::record('invariant_blocked', $context);

    config()->set('filament-actionguard.audit.include_model_type', true);
    ActionGuardAudit::record('invariant_blocked', $context);
    ActionGuardAudit::record('bypass_used', $context);
});

it('uses the default logger when no audit channel is configured', function () {
    config()->set('filament-actionguard.audit.enabled', true);
    config()->set('filament-actionguard.audit.channel');
    Log::shouldReceive('channel')->once()->with(null)->andReturnSelf();
    Log::shouldReceive('notice')->once()->with('filament-actionguard.action_evaluated', [
        'passed' => true,
        'failed' => 0,
        'errors' => 0,
    ]);

    ActionGuardAudit::record('action_evaluated', [
        'passed' => true,
        'failed' => 0,
        'errors' => 0,
    ]);
});
