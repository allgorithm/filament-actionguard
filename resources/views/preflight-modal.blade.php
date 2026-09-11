<div class="filament-actionguard-modal" style="display: flex; flex-direction: column; gap: 1rem; padding: 0.25rem 0;">
    <div style="display: flex; flex-direction: column; gap: 0.625rem;">
        @foreach($result->checks as $check)
            <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid rgba(156, 163, 175, 0.25); background: rgba(156, 163, 175, 0.04);">
                <div style="flex-shrink: 0; width: 1.25rem; height: 1.25rem; min-width: 1.25rem; min-height: 1.25rem; margin-top: 0.125rem;">
                    @if($check->isPassed())
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 1.25rem; height: 1.25rem; min-width: 1.25rem; min-height: 1.25rem; display: block;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    @else
                        @if($check->required)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 1.25rem; height: 1.25rem; min-width: 1.25rem; min-height: 1.25rem; display: block;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 1.25rem; height: 1.25rem; min-width: 1.25rem; min-height: 1.25rem; display: block;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        @endif
                    @endif
                </div>

                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <span style="font-weight: 600; font-size: 0.875rem;">
                            {{ $check->label }}
                        </span>
                        @if($check->required)
                            <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; {{ $check->isPassed() ? 'background: #ecfdf5; color: #047857;' : 'background: #fef2f2; color: #b91c1c;' }}">
                                {{ $check->isPassed() ? __('filament-actionguard::ui.status.passed') : __('filament-actionguard::ui.status.required') }}
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background: #f3f4f6; color: #4b5563;">
                                {{ __('filament-actionguard::ui.status.optional') }}
                            </span>
                        @endif
                    </div>

                    @if(!$check->isPassed() && $check->message)
                        <p style="font-size: 0.8125rem; color: #dc2626; margin-top: 0.25rem; line-height: 1.4;">
                            {{ $check->message }}
                        </p>
                    @endif

                    @if(!$check->isPassed() && $check->resolution)
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; padding: 0.5rem; border-radius: 0.375rem; background: rgba(0,0,0,0.03); border: 1px dashed rgba(156, 163, 175, 0.4);">
                            @if($check->resolution instanceof \Allgorithm\FilamentActionGuard\Results\CheckResolution)
                                @if($check->resolution->url)
                                    <a href="{{ $check->resolution->url }}" target="_blank" rel="noopener noreferrer" class="underline text-primary-600">
                                        {{ $check->resolution->label }}
                                    </a>
                                @else
                                    {{ $check->resolution->label }}
                                @endif
                            @else
                                {{ $check->resolution }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div style="padding: 0.75rem 1rem; border-radius: 0.5rem; text-align: center; font-size: 0.875rem; font-weight: 600; border: 1px solid; {{ $result->passed ? 'background: #ecfdf5; border-color: #a7f3d0; color: #065f46;' : 'background: #fef2f2; border-color: #fecaca; color: #991b1b;' }}">
        @if($result->passed)
            ✓ {{ __('filament-actionguard::ui.modal.passed_summary') }}
        @else
            ✕ {{ __('filament-actionguard::ui.modal.failed_summary', ['failed' => $result->summary['failed'] + $result->summary['errors'], 'total' => $result->summary['total']]) }}
        @endif
    </div>
</div>
