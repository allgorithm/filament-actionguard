<?php

use Allgorithm\FilamentActionGuard\Actions\ActionGuardAction;
use Allgorithm\FilamentActionGuard\Bridge\BusinessCoreContractMap;
use Allgorithm\FilamentActionGuard\Bridge\EnterpriseBridgeResolver;
use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Illuminate\Database\Eloquent\Model;

interface SecTestOperationContract
{
    public function descriptor(): SecTestOperationDescriptor;
}

final readonly class SecTestOperationDescriptor
{
    public function __construct(public array $guards) {}
}

interface SecTestGuardContract
{
    public function check(mixed $target, SecTestOperationContext $context): SecTestCoreCheckResult;
}

final readonly class SecTestActorContext
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

final readonly class SecTestOperationContext
{
    public function __construct(
        public SecTestActorContext $actor,
        public string $correlationId,
        public array $metadata = [],
    ) {}
}

final readonly class SecTestCoreResolution
{
    public function __construct(
        public string $label,
        public ?string $action = null,
    ) {}
}

final readonly class SecTestCoreCheckResult
{
    public function __construct(
        public string $key,
        public string $label,
        public string $status,
        public bool $required = true,
        public ?string $message = null,
        public ?SecTestCoreResolution $resolution = null,
    ) {}
}

class SecTestMaliciousGuard implements SecTestGuardContract
{
    public static ?string $payloadUrl = 'javascript:alert(1)';

    public static string $payloadLabel = '<script>alert("xss")</script>';

    public function check(mixed $target, SecTestOperationContext $context): SecTestCoreCheckResult
    {
        return new SecTestCoreCheckResult(
            key: 'sec_test',
            label: 'Security Check',
            status: 'fail',
            required: true,
            message: 'Action blocked by compliance policy.',
            resolution: new SecTestCoreResolution(
                label: static::$payloadLabel,
                action: static::$payloadUrl,
            ),
        );
    }
}

class SecTestMaliciousOperation implements SecTestOperationContract
{
    public function descriptor(): SecTestOperationDescriptor
    {
        return new SecTestOperationDescriptor([SecTestMaliciousGuard::class]);
    }
}

function bindSecBridge(): void
{
    $contracts = new BusinessCoreContractMap(
        operationContract: SecTestOperationContract::class,
        descriptor: SecTestOperationDescriptor::class,
        guardContract: SecTestGuardContract::class,
        actorContext: SecTestActorContext::class,
        operationContext: SecTestOperationContext::class,
        checkResult: SecTestCoreCheckResult::class,
    );

    app()->instance(EnterpriseBridgeResolver::class, new EnterpriseBridgeResolver($contracts));
}

beforeEach(function () {
    bindSecBridge();
});

it('blocks javascript: scheme and escapes html labels from enterprise resolutions', function (string $dangerousUrl) {
    SecTestMaliciousGuard::$payloadUrl = $dangerousUrl;
    SecTestMaliciousGuard::$payloadLabel = '<script>alert("xss")</script>';

    $action = ActionGuardAction::make('publish')->operation(SecTestMaliciousOperation::class);
    $html = $action->evaluateAndRender(new class extends Model {})->render();

    // The dangerous URL must never appear in the output
    expect($html)->not->toContain($dangerousUrl)
        ->and($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
})->with([
    'javascript:alert(1)',
    'JAVASCRIPT:alert(document.cookie)',
    'javascript:/*--></title></style></textarea><em><script>alert(1)</script>',
    'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
    'vbscript:msgbox(1)',
    '//attacker.com/phishing',
    "https://example.com\r\nX-Injected: header",
    "https://example.com\0evil",
]);

it('escapes raw strings passed directly to CheckResult resolution', function () {
    $action = ActionGuardAction::make('publish')->checks([
        new class implements ActionGuardCheckContract
        {
            public function evaluate(Model $record): CheckResult
            {
                return CheckResult::fail(
                    key: 'raw_xss',
                    label: 'Raw XSS Test',
                    message: 'Failure message',
                    resolution: '<b onmouseover="alert(1)">Hover me</b>'
                );
            }
        },
    ]);

    $html = $action->evaluateAndRender(new class extends Model {})->render();

    expect($html)->not->toContain('<b onmouseover="alert(1)">')
        ->and($html)->toContain('&lt;b onmouseover=&quot;alert(1)&quot;&gt;Hover me&lt;/b&gt;');
});

it('allows safe HTTPS and local relative URLs and sets noopener noreferrer', function (string $safeUrl) {
    SecTestMaliciousGuard::$payloadUrl = $safeUrl;
    SecTestMaliciousGuard::$payloadLabel = 'Review compliance documents';

    $action = ActionGuardAction::make('publish')->operation(SecTestMaliciousOperation::class);
    $html = $action->evaluateAndRender(new class extends Model {})->render();

    expect($html)->toContain('href="'.$safeUrl.'"')
        ->and($html)->toContain('rel="noopener noreferrer"')
        ->and($html)->toContain('target="_blank"')
        ->and($html)->toContain('Review compliance documents');
})->with([
    'https://billing.example.com/invoice/123',
    '/admin/compliance/review',
]);

it('blocks HTTP resolution URLs unless the explicit compatibility switch is enabled', function () {
    SecTestMaliciousGuard::$payloadUrl = 'http://localhost:8000/fix';
    SecTestMaliciousGuard::$payloadLabel = 'Local compatibility link';

    $action = ActionGuardAction::make('publish')->operation(SecTestMaliciousOperation::class);
    expect($action->evaluateAndRender(new class extends Model {})->render())
        ->not->toContain('href="http://localhost:8000/fix"');

    config()->set('filament-actionguard.allow_insecure_resolution_urls', true);

    expect($action->evaluateAndRender(new class extends Model {})->render())
        ->toContain('href="http://localhost:8000/fix"');
});
