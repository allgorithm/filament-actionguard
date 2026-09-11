<?php

use Allgorithm\FilamentActionGuard\Actions\ActionGuardAction;
use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

it('executes the encapsulated action when all checks pass', function () {
    $executed = false;

    $action = ActionGuardAction::make('publish')
        ->checks([
            new class implements ActionGuardCheckContract
            {
                public function evaluate(Model $record): CheckResult
                {
                    return CheckResult::pass('test', 'Test Pass');
                }
            },
        ])
        ->action(function () use (&$executed) {
            $executed = true;
        });

    $record = new class extends Model {};

    // Simulate Filament Action execution
    $action->record($record);
    $action->call(['record' => $record]); // Or equivalent mock call

    expect($executed)->toBeTrue();
});

it('blocks the encapsulated action when a required check fails (Critical Invariant)', function () {
    $executed = false;

    $action = ActionGuardAction::make('publish')
        ->checks([
            new class implements ActionGuardCheckContract
            {
                public function evaluate(Model $record): CheckResult
                {
                    return CheckResult::fail('test', 'Test Fail', 'Error Message');
                }
            },
        ])
        ->action(function () use (&$executed) {
            $executed = true;
        });

    $record = new class extends Model {};

    // Simulate Filament Action execution (outside Livewire, we trigger lifecycle manually)
    try {
        $action->record($record);
        $action->callBefore();
        $action->call(['record' => $record]); // Or equivalent mock call
    } catch (Halt $e) {
        // Halt exception is thrown
    }

    expect($executed)->toBeFalse();
});

it('executes the encapsulated action when only an optional check fails', function () {
    $executed = false;

    $action = ActionGuardAction::make('publish')
        ->checks([
            new class implements ActionGuardCheckContract
            {
                public function evaluate(Model $record): CheckResult
                {
                    return CheckResult::pass('req_check', 'Required Pass');
                }
            },
            new class implements ActionGuardCheckContract
            {
                public function evaluate(Model $record): CheckResult
                {
                    return CheckResult::fail('opt_check', 'Optional Fail', 'Notice only', required: false);
                }
            },
        ])
        ->action(function () use (&$executed) {
            $executed = true;
        });

    $record = new class extends Model {};
    $action->record($record);
    $action->callBefore();
    $action->call(['record' => $record]);

    expect($executed)->toBeTrue();
});

it('fails closed when record is null and checks are configured', function () {
    $action = ActionGuardAction::make('publish')
        ->checks([
            new class implements ActionGuardCheckContract
            {
                public function evaluate(Model $record): CheckResult
                {
                    return CheckResult::pass('test', 'Test Pass');
                }
            },
        ]);

    $result = $action->evaluateChecks(null);

    expect($result->passed)->toBeFalse()
        ->and($result->summary['errors'])->toBe(1);

    expect(fn () => $action->record(null)->callBefore())->toThrow(Halt::class);
});

it('allows execution when record is null and no checks are configured', function () {
    $action = ActionGuardAction::make('publish');
    $result = $action->evaluateChecks(null);

    expect($result->passed)->toBeTrue()
        ->and($result->summary['total'])->toBe(0);
});

it('catches check exceptions during evaluateChecks and fails closed', function () {
    $action = ActionGuardAction::make('publish')
        ->checks([
            new class implements ActionGuardCheckContract
            {
                public function evaluate(Model $record): CheckResult
                {
                    throw new RuntimeException('Uncaught failure inside custom check');
                }
            },
        ]);

    $record = new class extends Model {};
    $result = $action->evaluateChecks($record);

    expect($result->passed)->toBeFalse()
        ->and($result->summary['errors'])->toBe(1)
        ->and($result->checks[0]->message)->not->toContain('Uncaught failure inside custom check')
        ->and($result->checks[0]->message)->toContain('Reference:');
});

it('renders preflight modal view with correct result payload', function () {
    $action = ActionGuardAction::make('publish')
        ->checks([
            new class implements ActionGuardCheckContract
            {
                public function evaluate(Model $record): CheckResult
                {
                    return CheckResult::pass('test', 'Test Passing Check');
                }
            },
        ]);

    $record = new class extends Model {};
    $rendered = $action->evaluateAndRender($record);

    expect($rendered)->toBeInstanceOf(View::class);
    $data = $rendered->getData();
    expect($data['passed'])->toBeTrue()
        ->and($data['result']->summary['passed'])->toBe(1);
});
