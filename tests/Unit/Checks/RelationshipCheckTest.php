<?php

use Allgorithm\FilamentActionGuard\Checks\RelationshipCheck;
use Allgorithm\FilamentActionGuard\Results\CheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

it('passes when relationship is present', function () {
    $model = new class extends Model
    {
        public function category() {}
    };
    $model->setRelation('category', (object) ['name' => 'Tech']);

    $check = RelationshipCheck::make('category');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
});

it('fails when relationship is null', function () {
    $model = new class extends Model
    {
        public function category() {}
    };
    $model->setRelation('category', null);

    $check = RelationshipCheck::make('category');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL);
});

it('fails when relationship is an empty collection', function () {
    $model = new class extends Model
    {
        public function items() {}
    };
    $model->setRelation('items', new Collection);

    $check = RelationshipCheck::make('items');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL);
});

it('returns error status when relationship method does not exist', function () {
    $model = new class extends Model {};

    $check = RelationshipCheck::make('non_existent_relation');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::ERROR)
        ->and($result->message)->toContain("Relationship 'non_existent_relation' does not exist");
});

it('returns error status when relationship resolution throws an exception', function () {
    $model = new class extends Model
    {
        public function problematicRelation()
        {
            throw new RuntimeException('Database query failed for relation');
        }
    };

    $check = RelationshipCheck::make('problematicRelation');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::ERROR)
        ->and($result->message)->toContain('could not be resolved');
});

it('respects optional setting in RelationshipCheck', function () {
    $model = new class extends Model
    {
        public function category() {}
    };
    $model->setRelation('category', null);

    $check = RelationshipCheck::make('category')->optional();
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL)
        ->and($result->required)->toBeFalse();
});
