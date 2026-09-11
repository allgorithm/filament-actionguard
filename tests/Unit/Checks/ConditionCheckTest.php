<?php

use Allgorithm\FilamentActionGuard\Checks\ConditionCheck;
use Allgorithm\FilamentActionGuard\Results\CheckStatus;
use Illuminate\Database\Eloquent\Model;

it('passes when condition closure returns true', function () {
    $model = new class extends Model
    {
        protected $attributes = ['price' => 50];
    };

    $check = ConditionCheck::make('min_price', fn ($record) => $record->price >= 50);
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
});

it('fails with custom failure message when condition returns false', function () {
    $model = new class extends Model
    {
        protected $attributes = ['price' => 10];
    };

    $check = ConditionCheck::make('min_price', fn ($record) => $record->price >= 50, 'Price too low');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL)
        ->and($result->message)->toBe('Price too low');
});

it('returns error when condition closure is missing', function () {
    $model = new class extends Model {};

    $check = ConditionCheck::make('unconfigured');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::ERROR);
});

it('returns error when condition closure throws an exception', function () {
    $model = new class extends Model {};

    $check = ConditionCheck::make('throwing', function () {
        throw new RuntimeException('Database connection lost during condition');
    });
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::ERROR)
        ->and($result->message)->not->toContain('Database connection lost during condition')
        ->and($result->message)->toContain('Reference:');
});

it('respects optional setting in ConditionCheck', function () {
    $model = new class extends Model {};

    $check = ConditionCheck::make('test', fn () => false)->optional();
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL)
        ->and($result->required)->toBeFalse();
});
