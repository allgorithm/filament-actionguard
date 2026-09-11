<?php

use Allgorithm\FilamentActionGuard\Actions\ActionGuardAction;
use Allgorithm\FilamentActionGuard\Bridge\BusinessCoreContractMap;
use Allgorithm\FilamentActionGuard\Bridge\EnterpriseBridgeResolver;
use Allgorithm\FilamentActionGuard\Contracts\OperationContextFactoryContract;
use Allgorithm\FilamentActionGuard\Results\CheckStatus;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

interface TestOperationContract
{
    public function descriptor(): TestOperationDescriptor;
}

interface TestGuardContract
{
    public function check(mixed $target, TestOperationContext $context): TestCoreCheckResult;
}

final readonly class TestActorContext
{
    public function __construct(
        public string $actorId,
        public array $roles = [],
        public array $permissions = [],
        public ?string $tenantId = null,
        public ?string $organizationId = null,
        public array $metadata = [],
    ) {}
}

final readonly class TestOperationContext
{
    public function __construct(
        public TestActorContext $actor,
        public string $correlationId,
        public array $metadata = [],
    ) {}
}

final class TestOperationContextFactory implements OperationContextFactoryContract
{
    public function make(
        Model $record,
        BusinessCoreContractMap $contracts,
        string $correlationId,
    ): object {
        return new TestOperationContext(
            actor: new TestActorContext('business-core-user', tenantId: 'tenant-from-core'),
            correlationId: $correlationId,
            metadata: ['factory' => 'business-core'],
        );
    }
}

enum TestCoreStatus: string
{
    case PASS = 'pass';
    case FAIL = 'fail';
    case ERROR = 'error';
}

final readonly class TestCoreResolution
{
    public function __construct(public string $label, public ?string $action = null) {}
}

final readonly class TestCoreCheckResult
{
    public function __construct(
        public string $key,
        public string $label,
        public TestCoreStatus $status,
        public bool $required = true,
        public ?string $message = null,
        public ?TestCoreResolution $resolution = null,
        public array $metadata = [],
    ) {}
}

final readonly class TestOperationDescriptor
{
    public function __construct(public array $guards) {}
}

class TestPassingGuard implements TestGuardContract
{
    public static ?TestOperationContext $receivedContext = null;

    public function check(mixed $target, TestOperationContext $context): TestCoreCheckResult
    {
        self::$receivedContext = $context;

        return new TestCoreCheckResult('core_sku', 'Enterprise SKU Guard', TestCoreStatus::PASS);
    }
}

class TestFailingGuard implements TestGuardContract
{
    public function check(mixed $target, TestOperationContext $context): TestCoreCheckResult
    {
        return new TestCoreCheckResult(
            'core_price',
            'Enterprise Price Guard',
            TestCoreStatus::FAIL,
            message: 'Price must be approved.',
            resolution: new TestCoreResolution('Open Pricing', 'https://pricing.example.com/approve'),
        );
    }
}

class TestThrowingGuard implements TestGuardContract
{
    public function check(mixed $target, TestOperationContext $context): TestCoreCheckResult
    {
        throw new RuntimeException('Compliance service unavailable.');
    }
}

class TestMaliciousGuard implements TestGuardContract
{
    public function check(mixed $target, TestOperationContext $context): TestCoreCheckResult
    {
        return new TestCoreCheckResult(
            'unsafe',
            'Unsafe resolution',
            TestCoreStatus::FAIL,
            resolution: new TestCoreResolution('<script>alert(1)</script>', 'javascript:alert(1)'),
        );
    }
}

class TestPassingOperation implements TestOperationContract
{
    public function descriptor(): TestOperationDescriptor
    {
        return new TestOperationDescriptor([TestPassingGuard::class]);
    }
}

class TestMixedOperation implements TestOperationContract
{
    public function descriptor(): TestOperationDescriptor
    {
        return new TestOperationDescriptor([TestPassingGuard::class, TestFailingGuard::class]);
    }
}

class TestThrowingOperation implements TestOperationContract
{
    public function descriptor(): TestOperationDescriptor
    {
        return new TestOperationDescriptor([TestThrowingGuard::class]);
    }
}

class TestMaliciousOperation implements TestOperationContract
{
    public function descriptor(): TestOperationDescriptor
    {
        return new TestOperationDescriptor([TestMaliciousGuard::class]);
    }
}

class TestInvalidGuardOperation implements TestOperationContract
{
    public function descriptor(): TestOperationDescriptor
    {
        return new TestOperationDescriptor([stdClass::class]);
    }
}

function bindTestBridge(): void
{
    $contracts = new BusinessCoreContractMap(
        operationContract: TestOperationContract::class,
        descriptor: TestOperationDescriptor::class,
        guardContract: TestGuardContract::class,
        actorContext: TestActorContext::class,
        operationContext: TestOperationContext::class,
        checkResult: TestCoreCheckResult::class,
    );

    app()->instance(EnterpriseBridgeResolver::class, new EnterpriseBridgeResolver($contracts));
}

it('keeps Community mode independent and fails closed when operation is used without BusinessCore', function () {
    expect(fn () => ActionGuardAction::make('publish')->operation(TestPassingOperation::class))
        ->toThrow(LogicException::class, 'requires a compatible licensed installation');
});

it('rejects an unknown operation after the Enterprise contracts are available', function () {
    bindTestBridge();

    expect(fn () => ActionGuardAction::make('publish')->operation('UnknownEnterpriseOperation'))
        ->toThrow(InvalidArgumentException::class, 'does not exist');
});

it('rejects classes and guards that do not implement the licensed contracts', function () {
    bindTestBridge();

    expect(fn () => ActionGuardAction::make('publish')->operation(stdClass::class))
        ->toThrow(LogicException::class, 'must implement');

    expect(fn () => ActionGuardAction::make('publish')->operation(TestInvalidGuardOperation::class))
        ->toThrow(LogicException::class, 'must implement');
});

it('adapts typed Enterprise results and halts required failures', function () {
    bindTestBridge();
    $action = ActionGuardAction::make('publish')->operation(TestMixedOperation::class);
    $record = new class extends Model {};
    $result = $action->evaluateChecks($record);

    expect($result->passed)->toBeFalse()
        ->and($result->summary)->toMatchArray(['total' => 2, 'passed' => 1, 'failed' => 1, 'errors' => 0])
        ->and($result->checks[0]->status)->toBe(CheckStatus::PASS)
        ->and($result->checks[1]->status)->toBe(CheckStatus::FAIL)
        ->and($result->checks[1]->resolution->url)->toBe('https://pricing.example.com/approve');

    expect(fn () => $action->record($record)->callBefore())->toThrow(Halt::class);
});

it('creates the typed context automatically with actor and correlation id', function () {
    bindTestBridge();
    TestPassingGuard::$receivedContext = null;

    ActionGuardAction::make('publish')
        ->operation(TestPassingOperation::class)
        ->evaluateChecks(new class extends Model {});

    expect(TestPassingGuard::$receivedContext)->toBeInstanceOf(TestOperationContext::class)
        ->and(TestPassingGuard::$receivedContext->actor->actorId)->toBe('system')
        ->and(TestPassingGuard::$receivedContext->correlationId)->toBeUuid()
        ->and(TestPassingGuard::$receivedContext->metadata['bridge'])->toBe('filament-actionguard');
});

it('allows licensed BusinessCore to replace context construction without application changes', function () {
    bindTestBridge();
    app()->bind(OperationContextFactoryContract::class, TestOperationContextFactory::class);
    TestPassingGuard::$receivedContext = null;

    ActionGuardAction::make('publish')
        ->operation(TestPassingOperation::class)
        ->evaluateChecks(new class extends Model {});

    expect(TestPassingGuard::$receivedContext)->toBeInstanceOf(TestOperationContext::class)
        ->and(TestPassingGuard::$receivedContext->actor->actorId)->toBe('business-core-user')
        ->and(TestPassingGuard::$receivedContext->actor->tenantId)->toBe('tenant-from-core')
        ->and(TestPassingGuard::$receivedContext->metadata['factory'])->toBe('business-core');
});

it('allows execution when all Enterprise guards pass', function () {
    bindTestBridge();
    $executed = false;
    $record = new class extends Model {};
    $action = ActionGuardAction::make('publish')
        ->operation(TestPassingOperation::class)
        ->action(function () use (&$executed): void {
            $executed = true;
        });

    $action->record($record);
    $action->callBefore();
    $action->call(['record' => $record]);

    expect($executed)->toBeTrue();
});

it('fails closed when an Enterprise guard throws', function () {
    bindTestBridge();
    $action = ActionGuardAction::make('publish')->operation(TestThrowingOperation::class);
    $record = new class extends Model {};
    $result = $action->evaluateChecks($record);

    expect($result->passed)->toBeFalse()
        ->and($result->checks[0]->status)->toBe(CheckStatus::ERROR)
        ->and($result->checks[0]->message)->not->toContain('Compliance service unavailable')
        ->and($result->checks[0]->message)->toContain('Reference:');

    expect(fn () => $action->record($record)->callBefore())->toThrow(Halt::class);
});

it('neutralizes malicious resolution urls and escapes labels in the modal', function () {
    bindTestBridge();
    $action = ActionGuardAction::make('publish')->operation(TestMaliciousOperation::class);
    $record = new class extends Model {};
    $html = $action->evaluateAndRender($record)->render();

    expect($html)->not->toContain('javascript:')
        ->and($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

it('renders safe Enterprise links with reverse-tabnabbing protection', function () {
    bindTestBridge();
    $action = ActionGuardAction::make('publish')->operation(TestMixedOperation::class);
    $html = $action->evaluateAndRender(new class extends Model {})->render();

    expect($html)->toContain('https://pricing.example.com/approve')
        ->and($html)->toContain('rel="noopener noreferrer"');
});
