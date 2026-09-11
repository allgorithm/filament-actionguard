<?php

use Allgorithm\FilamentActionGuard\Results\ActionGuardResult;
use Allgorithm\FilamentActionGuard\Results\CheckResult;

it('calculates passed as true when all checks pass', function () {
    $result = ActionGuardResult::fromChecks([
        CheckResult::pass('check1', 'Check 1'),
        CheckResult::pass('check2', 'Check 2'),
    ]);

    expect($result->passed)->toBeTrue()
        ->and($result->summary['total'])->toBe(2)
        ->and($result->summary['passed'])->toBe(2)
        ->and($result->summary['failed'])->toBe(0)
        ->and($result->summary['errors'])->toBe(0);
});

it('calculates passed as false when a required check fails', function () {
    $result = ActionGuardResult::fromChecks([
        CheckResult::pass('check1', 'Check 1'),
        CheckResult::fail('check2', 'Check 2', 'Failed', required: true),
    ]);

    expect($result->passed)->toBeFalse()
        ->and($result->summary['failed'])->toBe(1);
});

it('allows passed to be true when only an optional check fails', function () {
    $result = ActionGuardResult::fromChecks([
        CheckResult::pass('check1', 'Check 1'),
        CheckResult::fail('check2', 'Check 2', 'Warning only', required: false),
    ]);

    expect($result->passed)->toBeTrue()
        ->and($result->summary['failed'])->toBe(1);
});

it('fails closed when any check produces an error', function () {
    $result = ActionGuardResult::fromChecks([
        CheckResult::pass('check1', 'Check 1'),
        CheckResult::error('check2', 'Check 2', 'Exception occurred'),
    ]);

    expect($result->passed)->toBeFalse()
        ->and($result->summary['errors'])->toBe(1);
});
