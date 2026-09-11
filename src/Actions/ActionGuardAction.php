<?php

namespace Allgorithm\FilamentActionGuard\Actions;

use Allgorithm\FilamentActionGuard\Bridge\EnterpriseBridgeResolver;
use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Results\ActionGuardResult;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Allgorithm\FilamentActionGuard\Support\ActionGuardAudit;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ActionGuardAction extends Action
{
    /** @var array<ActionGuardCheckContract> */
    protected array $checks = [];

    /** @var class-string|null */
    protected ?string $operation = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();
        $this->modalDescription(null);
        $this->modalWidth('lg');

        $rawLabel = $this->getLabel();
        $label = $rawLabel instanceof Htmlable ? strip_tags($rawLabel->toHtml()) : (string) ($rawLabel ?? '');
        $this->modalHeading(__('filament-actionguard::ui.modal.heading', ['label' => $label]));
        $this->modalContent(function (ActionGuardAction $action, ?Model $record) {
            return $action->evaluateAndRender($record);
        });

        $this->modalSubmitAction(function (Action $action, ActionGuardAction $parentAction, ?Model $record) {
            $result = $parentAction->evaluateChecks($record);
            if (! $result->passed) {
                return false;
            }

            return $action;
        });

        // Ensure action is only executed if checks pass
        $this->before(function (ActionGuardAction $action, ?Model $record) {
            $result = $action->evaluateChecks($record);
            if (! $result->passed) {
                // Halt the action execution
                $action->halt();
            }
        });
    }

    /**
     * @param  array<ActionGuardCheckContract|class-string>  $checks
     */
    public function checks(array $checks): static
    {
        $this->checks = array_map(function ($check) {
            if (is_string($check) && class_exists($check)) {
                $check = app($check);
            }

            if (! $check instanceof ActionGuardCheckContract) {
                throw new InvalidArgumentException('Every ActionGuard check must implement '.ActionGuardCheckContract::class.'.');
            }

            return $check;
        }, $checks);

        return $this;
    }

    /**
     * Enterprise Bridge Mode: Connects this action to an Allgorithm BusinessCore Operation.
     *
     * Guards declared in the OperationDescriptor are automatically resolved and executed
     * as ActionGuard checks in the preflight modal.
     *
     * @param  class-string  $operationClass
     */
    public function operation(string $operationClass): static
    {
        $this->operation = $operationClass;

        $guards = app(EnterpriseBridgeResolver::class)->resolveGuards($operationClass);
        $this->checks($guards);

        return $this;
    }

    protected ?string $targetState = null;

    /**
     * Binds this action to a specific state name, allowing automatic guard resolution
     * from models using the HasActionGuards trait.
     */
    public function forState(string $state): static
    {
        $this->targetState = $state;

        return $this;
    }

    public function evaluateChecks(?Model $record): ActionGuardResult
    {
        if (! config('filament-actionguard.enabled', true)) {
            return ActionGuardResult::fromChecks([]);
        }

        $checks = $this->checks;

        // Auto-resolve guards from model if none were explicitly set and model uses HasActionGuards
        if (empty($checks) && $record && method_exists($record, 'getGuardsForState')) {
            $state = $this->targetState ?? $this->getName();
            $checks = $record->getGuardsForState($state);
        }

        if (! $record) {
            if (! empty($checks)) {
                return ActionGuardResult::fromChecks([
                    CheckResult::error(
                        key: 'record',
                        label: __('filament-actionguard::ui.errors.record_label'),
                        message: __('filament-actionguard::ui.errors.record_missing'),
                    ),
                ], (bool) config('filament-actionguard.fail_closed', true));
            }

            return ActionGuardResult::fromChecks([]);
        }

        $results = [];
        foreach ($checks as $check) {
            try {
                $results[] = $check->evaluate($record);
            } catch (\Throwable $e) {
                $reference = (string) Str::uuid();
                report($e);

                $results[] = CheckResult::error(
                    key: 'check_error',
                    label: __('filament-actionguard::ui.errors.check_label'),
                    message: __('filament-actionguard::ui.errors.check_failed', ['reference' => $reference]),
                );
            }
        }

        $result = ActionGuardResult::fromChecks($results, (bool) config('filament-actionguard.fail_closed', true));

        ActionGuardAudit::record('action_evaluated', [
            'passed' => $result->passed,
            'failed' => $result->summary['failed'],
            'errors' => $result->summary['errors'],
        ]);

        return $result;
    }

    public function evaluateAndRender(?Model $record): HtmlString|View|string
    {
        $result = $this->evaluateChecks($record);

        return view('filament-actionguard::preflight-modal', [
            'result' => $result,
            'passed' => $result->passed,
        ]);
    }
}
