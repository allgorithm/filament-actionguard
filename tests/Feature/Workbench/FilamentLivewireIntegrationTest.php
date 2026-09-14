<?php

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\ProductResource\Pages\EditProduct;
use Workbench\App\Filament\Resources\ProductResource\Pages\ListProducts;
use Workbench\App\Models\Product;
use Workbench\App\Models\User;

beforeEach(function () {
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->timestamps();
    });

    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('sku')->nullable();
        $table->decimal('price', 10, 2)->nullable();
        $table->string('status')->default('draft');
        $table->string('image_url')->nullable();
        $table->foreignId('category_id')->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
    });

    $user = new User([
        'name' => 'ActionGuard Tester',
        'email' => 'tester@example.com',
        'password' => 'password',
    ]);
    $user->id = 1;
    $user->exists = true;

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag));
});

afterEach(function () {
    Schema::dropIfExists('products');
    Schema::dropIfExists('categories');
});

it('mounts and halts an incomplete table action inside Filament Livewire', function () {
    $product = Product::create([
        'name' => 'Incomplete product',
        'sku' => null,
        'price' => null,
        'image_url' => null,
        'status' => 'draft',
    ]);

    $testAction = TestAction::make('publish')->table($product);

    Livewire::test(ListProducts::class)
        ->assertTableActionVisible('publish', $product)
        ->mountAction($testAction)
        ->assertActionMounted($testAction)
        ->assertMountedActionModalSee(__('filament-actionguard::ui.modal.failed_summary', ['failed' => 4, 'total' => 5]))
        ->callMountedAction()
        ->assertActionHalted($testAction);

    expect($product->refresh()->status)->toBe('draft');
});

it('executes a complete table action inside Filament Livewire', function () {
    $product = Product::create([
        'name' => 'Complete product',
        'sku' => 'COMPLETE-1',
        'price' => 25,
        'image_url' => 'https://example.com/product.jpg',
        'status' => 'draft',
    ]);

    $testAction = TestAction::make('publish')->table($product);

    Livewire::test(ListProducts::class)
        ->mountAction($testAction)
        ->assertActionMounted($testAction)
        ->assertMountedActionModalSee(__('filament-actionguard::ui.modal.passed_summary'))
        ->callMountedAction()
        ->assertActionNotMounted();

    expect($product->refresh()->status)->toBe('published');
});

it('mounts and executes the edit page header action inside Filament Livewire', function () {
    $product = Product::create([
        'name' => 'Header product',
        'sku' => 'HEADER-1',
        'price' => 25,
        'image_url' => 'https://example.com/header.jpg',
        'status' => 'draft',
    ]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->mountAction('publish')
        ->assertActionMounted('publish')
        ->assertMountedActionModalSee(__('filament-actionguard::ui.modal.passed_summary'))
        ->callMountedAction()
        ->assertActionNotMounted();

    expect($product->refresh()->status)->toBe('published');
});

it('maps invariant failures to the edit form and can suppress notifications', function () {
    $product = Product::create([
        'name' => 'Protected product',
        'sku' => 'PROTECTED-1',
        'price' => 25,
        'image_url' => 'https://example.com/protected.jpg',
        'status' => 'published',
    ]);

    config()->set('filament-actionguard.notifications', false);
    session()->forget('filament.notifications');

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['image_url' => null])
        ->call('save')
        ->assertHasFormErrors(['image_url']);

    expect($product->refresh()->image_url)->toBe('https://example.com/protected.jpg')
        ->and(session()->get('filament.notifications'))->toBeNull();
});
