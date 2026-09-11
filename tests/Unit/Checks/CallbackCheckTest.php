<?php

use Allgorithm\FilamentActionGuard\Checks\CallbackCheck;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Allgorithm\FilamentActionGuard\Results\CheckStatus;
use Illuminate\Database\Eloquent\Model;

it('delegates evaluation to callback', function () {
    $model = new class extends Model {};

    $check = CallbackCheck::make('custom', fn ($m) => CheckResult::pass('custom', 'Custom OK'));
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
});

it('returns error when callback is missing', function () {
    $model = new class extends Model {};

    $check = CallbackCheck::make('missing_callback');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::ERROR);
});

it('returns error when callback throws an exception', function () {
    $model = new class extends Model {};

    $check = CallbackCheck::make('throwing', function () {
        throw new Exception('Callback crashed unexpectedly');
    });
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::ERROR)
        ->and($result->message)->toContain('Callback crashed unexpectedly');
});

it('returns error when callback does not return a CheckResult instance', function () {
    $model = new class extends Model {};

    /** @phpstan-ignore-next-line */
    $check = CallbackCheck::make('invalid_return', fn () => 'invalid string result');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::ERROR)
        ->and($result->message)->toContain('must return an instance of CheckResult');
});
