<?php

use Allgorithm\FilamentActionGuard\Checks\NotEmptyCheck;
use Allgorithm\FilamentActionGuard\Results\CheckStatus;
use Illuminate\Database\Eloquent\Model;

it('passes when field has non-empty content', function () {
    $model = new class extends Model
    {
        protected $attributes = ['price' => '29.99'];
    };

    $check = NotEmptyCheck::make('price');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
});

it('passes when field is 0 or string zero', function ($value) {
    $model = new class extends Model {};
    $model->setAttribute('count', $value);

    $check = NotEmptyCheck::make('count');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
})->with([
    0,
    0.0,
    '0',
]);

it('passes when field is non-empty array or countable', function ($value) {
    $model = new class extends Model {};
    $model->setAttribute('tags', $value);

    $check = NotEmptyCheck::make('tags');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
})->with([
    [['red', 'blue']],
    [collect(['red', 'blue'])],
]);

it('fails when field is empty string or only whitespace', function ($emptyValue) {
    $model = new class extends Model {};
    $model->setAttribute('price', $emptyValue);

    $check = NotEmptyCheck::make('price');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL);
})->with([
    null,
    '',
    '   ',
    [[]],
    [collect([])],
]);

it('respects optional setting in NotEmptyCheck', function () {
    $model = new class extends Model {};
    $model->setAttribute('notes', '');

    $check = NotEmptyCheck::make('notes')->optional();
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL)
        ->and($result->required)->toBeFalse();
});
