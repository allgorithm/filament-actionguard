<?php

namespace Allgorithm\FilamentActionGuard\Results;

final readonly class ActionGuardResult
{
    /**
     * @param  array<CheckResult>  $checks
     * @param  array{total: int, passed: int, failed: int, errors: int}  $summary
     */
    public function __construct(
        public bool $passed,
        public array $checks,
        public array $summary,
    ) {}

    /**
     * @param  array<CheckResult>  $checks
     */
    public static function fromChecks(array $checks, bool $failClosed = true): self
    {
        $passedCount = 0;
        $failedCount = 0;
        $errorCount = 0;

        $overallPassed = true;

        foreach ($checks as $check) {
            if ($check->status === CheckStatus::PASS) {
                $passedCount++;
            } elseif ($check->status === CheckStatus::FAIL) {
                $failedCount++;
                if ($check->required) {
                    $overallPassed = false;
                }
            } elseif ($check->status === CheckStatus::ERROR) {
                $errorCount++;
                if ($failClosed) {
                    $overallPassed = false;
                }
            }
        }

        return new self(
            passed: $overallPassed,
            checks: $checks,
            summary: [
                'total' => count($checks),
                'passed' => $passedCount,
                'failed' => $failedCount,
                'errors' => $errorCount,
            ]
        );
    }
}
