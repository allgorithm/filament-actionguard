<?php

use Allgorithm\FilamentActionGuard\Checks\MediaCheck;
use Allgorithm\FilamentActionGuard\Results\CheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

it('passes when model attribute contains an image URL', function () {
    $model = new class extends Model
    {
        protected $attributes = ['image_url' => 'https://example.com/photo.jpg'];
    };

    $check = MediaCheck::make('image_url');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
});

it('passes when model has a non-empty media array', function () {
    $model = new class extends Model
    {
        protected $attributes = ['gallery' => ['img1.png', 'img2.png']];
    };

    $check = MediaCheck::make('gallery');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
});

it('passes when model uses getMedia() and has media items', function () {
    $model = new class extends Model
    {
        public function getMedia(string $collection)
        {
            return new Collection(['item1']);
        }
    };

    $check = MediaCheck::make('images');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::PASS);
});

it('fails when no media is present', function () {
    $model = new class extends Model
    {
        protected $attributes = ['image_url' => null];
    };

    $check = MediaCheck::make('image_url');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL);
});

it('fails when media attribute is an empty array', function () {
    $model = new class extends Model
    {
        protected $attributes = ['gallery' => []];
    };

    $check = MediaCheck::make('gallery');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL);
});

it('fails when getMedia() returns an empty collection', function () {
    $model = new class extends Model
    {
        public function getMedia(string $collection)
        {
            return new Collection([]);
        }
    };

    $check = MediaCheck::make('images');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL);
});

it('returns error when getMedia() throws an exception', function () {
    $model = new class extends Model
    {
        public function getMedia(string $collection)
        {
            throw new RuntimeException('Media library disk unreachable');
        }
    };

    $check = MediaCheck::make('images');
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::ERROR)
        ->and($result->message)->toContain('Media library disk unreachable');
});

it('respects optional setting in MediaCheck', function () {
    $model = new class extends Model
    {
        protected $attributes = ['image_url' => null];
    };

    $check = MediaCheck::make('image_url')->optional();
    $result = $check->evaluate($model);

    expect($result->status)->toBe(CheckStatus::FAIL)
        ->and($result->required)->toBeFalse();
});
