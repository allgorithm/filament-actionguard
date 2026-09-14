<?php

declare(strict_types=1);

return [
    /*
     * Disabling the package is an explicit operational decision. Keep this enabled
     * in production whenever a model or action relies on ActionGuard invariants.
     */
    'enabled' => (bool) env('ACTIONGUARD_ENABLED', true),

    /* Errors in a check must block the operation unless explicitly disabled. */
    'fail_closed' => (bool) env('ACTIONGUARD_FAIL_CLOSED', true),

    /* A bypass is disabled by default and is always scoped to one callback. */
    'allow_bypass' => (bool) env('ACTIONGUARD_ALLOW_BYPASS', false),

    'notifications' => (bool) env('ACTIONGUARD_NOTIFICATIONS_ENABLED', true),

    /*
     * Model types and state values are excluded by default and require separate,
     * explicit opt-ins. Messages, record IDs, actor IDs, and arbitrary model
     * attributes are never part of the package's audit context.
     */
    'audit' => [
        'enabled' => (bool) env('ACTIONGUARD_AUDIT_TRAIL', false),
        'channel' => env('ACTIONGUARD_AUDIT_CHANNEL'),
        'include_model_type' => (bool) env('ACTIONGUARD_AUDIT_INCLUDE_MODEL_TYPE', false),
        'include_state' => (bool) env('ACTIONGUARD_AUDIT_INCLUDE_STATE', false),
    ],

    /* Relative URLs and HTTPS are allowed; HTTP requires an explicit opt-in. */
    'allow_insecure_resolution_urls' => (bool) env('ACTIONGUARD_ALLOW_INSECURE_RESOLUTION_URLS', false),
];
