<?php

use Allgorithm\FilamentActionGuard\Checks\RequiredFieldCheck;
use Allgorithm\FilamentActionGuard\Results\CheckStatus;
use Illuminate\Database\Eloquent\Model;

it('passes when the required field has a value', function () {
    $model = new class extends Model
    {
        protected $attributes = ['name' => 'Valid Product'];
    };

    $check = RequiredFieldCheck::make('name');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS)
        ->and($result->isPassed())->toBeTrue();
});

it('fails when the required field is null', function () {
    $model = new class extends Model
    {
        protected $attributes = ['name' => null];
    };

    $check = RequiredFieldCheck::make('name')->label('Produktname');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL)
        ->and($result->isPassed())->toBeFalse()
        ->and($result->label)->toBe('Produktname')
        ->and($result->required)->toBeTrue();
});

it('respects optional setting', function () {
    $model = new class extends Model
    {
        protected $attributes = ['sku' => null];
    };

    $check = RequiredFieldCheck::make('sku')->optional();
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL)
        ->and($result->required)->toBeFalse();
});
