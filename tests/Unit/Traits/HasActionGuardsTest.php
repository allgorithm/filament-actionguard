<?php

use Allgorithm\FilamentActionGuard\Actions\ActionGuardAction;
use Allgorithm\FilamentActionGuard\Checks\MediaCheck;
use Allgorithm\FilamentActionGuard\Checks\NotEmptyCheck;
use Allgorithm\FilamentActionGuard\Checks\RequiredFieldCheck;
use Allgorithm\FilamentActionGuard\Exceptions\StateInvariantViolationException;
use Allgorithm\FilamentActionGuard\Traits\HasActionGuards;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Schema::create('test_articles', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->string('slug')->nullable();
        $table->string('image_url')->nullable();
        $table->string('status')->default('draft');
        $table->timestamps();
    });

    Schema::create('test_comments', function (Blueprint $table) {
        $table->id();
        $table->string('content')->nullable();
        $table->string('moderation_state')->default('pending');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_articles');
    Schema::dropIfExists('test_comments');
});

// Test Article Model using HasActionGuards
class TestArticle extends Model
{
    use HasActionGuards;

    protected $table = 'test_articles';

    protected $guarded = [];

    public function actionGuards(): array
    {
        return [
            'published' => [
                RequiredFieldCheck::make('title')->label('Article Title'),
                RequiredFieldCheck::make('slug')->label('URL Slug'),
                MediaCheck::make('image_url')->label('Cover Image'),
            ],
        ];
    }
}

// Test Comment Model with custom state column
class TestComment extends Model
{
    use HasActionGuards;

    protected $table = 'test_comments';

    protected $guarded = [];

    protected string $actionGuardStateColumn = 'moderation_state';

    public function actionGuards(): array
    {
        return [
            'approved' => [
                NotEmptyCheck::make('content')->label('Comment Content'),
            ],
        ];
    }
}

it('allows saving when model is in an unprotected draft state', function () {
    $article = new TestArticle([
        'title' => null,
        'slug' => null,
        'image_url' => null,
        'status' => 'draft',
    ]);

    expect($article->save())->toBeTrue();
    expect($article->id)->not->toBeNull();
});

it('allows saving when model is in protected state and all guards pass', function () {
    $article = new TestArticle([
        'title' => 'Breaking News',
        'slug' => 'breaking-news',
        'image_url' => 'https://example.com/cover.jpg',
        'status' => 'published',
    ]);

    expect($article->save())->toBeTrue();
});

it('prevents saving and throws StateInvariantViolationException when a published model misses required data', function () {
    $article = TestArticle::create([
        'title' => 'Valid News',
        'slug' => 'valid-news',
        'image_url' => 'https://example.com/cover.jpg',
        'status' => 'published',
    ]);

    // Admin subsequently removes the image from the published article
    $article->image_url = null;

    try {
        $article->save();
        $this->fail('Expected StateInvariantViolationException was not thrown.');
    } catch (StateInvariantViolationException $e) {
        expect($e)->toBeInstanceOf(ValidationException::class)
            ->and($e->state)->toBe('published')
            ->and($e->errors())->toHaveKey('image_url')
            ->and($e->errors())->toHaveKey('data.image_url');
    }

    // Refresh from DB: Image was NOT modified in the database
    $article->refresh();
    expect($article->image_url)->toBe('https://example.com/cover.jpg');
});

it('dispatches a danger notification with failed criteria details when an invariant is violated', function () {
    $article = TestArticle::create([
        'title' => 'Article',
        'slug' => 'article',
        'image_url' => 'https://example.com/cover.jpg',
        'status' => 'published',
    ]);

    $article->image_url = null;

    try {
        $article->save();
    } catch (StateInvariantViolationException $e) {
        // Expected
    }

    $notifications = session()->get('filament.notifications');
    expect($notifications)->toBeArray()->not->toBeEmpty();

    $notification = end($notifications);
    expect($notification['status'])->toBe('danger')
        ->and($notification['title'])->toBe(__('filament-actionguard::ui.post_save.title'))
        ->and($notification['body'])->toContain('Cover Image');
});

it('allows bypassing guards using withoutActionGuards callback', function () {
    $article = TestArticle::create([
        'title' => 'Article',
        'slug' => 'article',
        'image_url' => 'https://example.com/img.jpg',
        'status' => 'published',
    ]);

    $article->image_url = null;

    config()->set('filament-actionguard.allow_bypass', true);

    $saved = TestArticle::withoutActionGuards(function () use ($article) {
        return $article->save();
    });

    expect($saved)->toBeTrue();
    $article->refresh();
    expect($article->image_url)->toBeNull();
});

it('rejects bypasses unless explicitly enabled', function () {
    config()->set('filament-actionguard.allow_bypass', false);

    expect(fn () => TestArticle::withoutActionGuards(fn () => null))
        ->toThrow(LogicException::class);
});

it('supports custom state column names', function () {
    $comment = new TestComment([
        'content' => '',
        'moderation_state' => 'approved',
    ]);

    expect(fn () => $comment->save())->toThrow(StateInvariantViolationException::class);

    // Provide content and saving succeeds
    $comment->content = 'Great article!';
    expect($comment->save())->toBeTrue();
});

it('enables ActionGuardAction to automatically resolve checks from model when using forState', function () {
    $article = new TestArticle([
        'title' => 'Draft with missing image',
        'slug' => 'draft-slug',
        'image_url' => null,
        'status' => 'draft',
    ]);

    // Action without explicit checks, binding to 'published' state
    $action = ActionGuardAction::make('publish')->forState('published');
    $result = $action->evaluateChecks($article);

    // Automatically inherited 3 checks from model actionGuards()['published']
    expect($result->checks)->toHaveCount(3)
        ->and($result->passed)->toBeFalse()
        ->and($result->summary['failed'])->toBe(1); // image_url fails
});
