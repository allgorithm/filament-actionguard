<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Exceptions;

use Allgorithm\FilamentActionGuard\Results\ActionGuardResult;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Thrown when an Eloquent model in a protected state fails its ActionGuards on save.
 * Extends ValidationException so Filament and Laravel automatically display field-level errors.
 */
class StateInvariantViolationException extends ValidationException
{
    /**
     * @param  array<string, mixed>  $messages
     */
    public function __construct(
        public readonly Model $model,
        public readonly string $state,
        public readonly ActionGuardResult $guardResult,
        array $messages = []
    ) {
        $validator = validator([], []);
        parent::__construct($validator);

        $this->errorBag = 'default';
        $this->response = null;

        // Populate field error messages
        foreach ($messages as $field => $fieldMessages) {
            foreach ((array) $fieldMessages as $msg) {
                $validator->errors()->add($field, $msg);
            }
        }
    }

    public static function fromResult(Model $model, string $state, ActionGuardResult $result): self
    {
        $messages = [];

        foreach ($result->checks as $check) {
            if ($check->isFailed() && $check->required) {
                $field = $check->key;
                // Strip prefixes like "media." if present
                if (str_starts_with($field, 'media.')) {
                    $field = substr($field, 6);
                }

                $msg = $check->message ?: "Field '{$check->label}' does not meet required state guards for state '{$state}'.";

                // Add to standard field key
                $messages[$field][] = $msg;

                // Add to Filament form data path key (e.g. data.image_url)
                $messages['data.'.$field][] = $msg;
            }
        }

        $exception = new self($model, $state, $result, $messages);
        $exception->sendNotification();

        return $exception;
    }

    /**
     * Sends a prominent Filament notification explaining the invariant violation.
     */
    public function sendNotification(): void
    {
        if (! config('filament-actionguard.notifications', true) || ! class_exists(Notification::class)) {
            return;
        }

        try {
            $failedChecks = array_filter(
                $this->guardResult->checks,
                fn ($check) => $check->isFailed() && $check->required
            );

            $bulletPoints = [];
            foreach ($failedChecks as $check) {
                $label = $check->label ?: $check->key;
                $msg = $check->message ?: $label;
                $bulletPoints[] = "• {$msg}";
            }

            $intro = __('filament-actionguard::ui.post_save.body', ['state' => $this->state]);
            $body = ! empty($bulletPoints)
                ? $intro."\n".implode("\n", $bulletPoints)
                : $intro;

            Notification::make()
                ->title(__('filament-actionguard::ui.post_save.title'))
                ->icon('heroicon-o-shield-exclamation')
                ->body($body)
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable) {
            // Failsafe: session not initialized or CLI context
        }
    }
}
