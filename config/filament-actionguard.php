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
     * The package never logs model attributes, messages, record IDs, or actor IDs.
     * Applications may route this channel to their central audit system.
     */
    'audit' => [
        'enabled' => (bool) env('ACTIONGUARD_AUDIT_TRAIL', false),
        'channel' => env('ACTIONGUARD_AUDIT_CHANNEL'),
    ],

    /* Relative URLs and HTTPS are allowed; HTTP requires an explicit opt-in. */
    'allow_insecure_resolution_urls' => (bool) env('ACTIONGUARD_ALLOW_INSECURE_RESOLUTION_URLS', false),
];
