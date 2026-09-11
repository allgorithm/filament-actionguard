<?php

return [
    'modal' => [
        'heading' => ':label',
        'subheading' => 'ActionGuard-Check',
        'passed_summary' => 'All checks passed. The action can be executed.',
        'failed_summary' => 'The action cannot be executed yet. :failed of :total checks failed.',
        'cancel' => 'Cancel',
    ],
    'status' => [
        'passed' => 'Passed',
        'required' => 'Required',
        'optional' => 'Optional',
    ],
    'post_save' => [
        'title' => 'Save prevented: Invariant criteria not met',
        'body' => 'The record is in protected state \':state\'. The following criteria are not met:',
    ],
    'errors' => [
        'record_label' => 'Model Record',
        'record_missing' => 'No model record was provided for the checks.',
        'check_label' => 'Check Error',
        'check_failed' => 'The check could not be completed. Reference: :reference',
        'invariant_label' => 'State Invariant Error',
        'invariant_failed' => 'The invariant check could not be completed. Reference: :reference',
    ],
    'enterprise' => [
        'label' => 'Enterprise Guard',
        'error_label' => 'Enterprise Guard Error',
        'unresolvable' => 'The Enterprise guard could not be resolved.',
        'not_callable' => 'The Enterprise guard does not expose a callable check method.',
        'invalid_result' => 'The Enterprise guard returned an incompatible result.',
        'failed' => 'The Enterprise guard could not be completed. Reference: :reference',
    ],
];
