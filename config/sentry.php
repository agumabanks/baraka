<?php

return [
    // The integration with Laravel is enabled by default. You can
    // disable the integration by setting 'enable' to false.
    'enable' => env('SENTRY_ENABLED', true),

    // The DSN tells the SDK where to send the events. If this value
    // is absent, the SDK will try to read it from the SENTRY_LARAVEL_DSN
    // environment variable. If that variable is also not present, the SDK
    // will just not send any events.
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // The sample rate for tracing. It must be a float between 0.0 and 1.0.
    // By default, we will capture 100% of transactions. We recommend
    // adjusting this value in production.
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // The sample rate for error events. It must be a float between 0.0 and 1.0.
    // By default, we will capture 100% of error events. We recommend
    // adjusting this value in production.
    'error_sample_rate' => (float) env('SENTRY_ERROR_SAMPLE_RATE', 1.0),

    // Attach stack traces to all messages.
    'attach_stacktrace' => true,

    // Set the environment
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    // Release version
    'release' => env('SENTRY_RELEASE', env('APP_VERSION')),

    // Performance Monitoring
    'before_send_transaction' => function (\Sentry\Tracing\Transaction $transaction) {
        // Filter out health check requests
        if (str_contains($transaction->getName(), 'health') ||
            str_contains($transaction->getName(), 'status')) {
            return null;
        }

        return $transaction;
    },

    // Error filtering
    'before_send' => function (\Sentry\Event $event) {
        // Ignore 404s in production
        if ($event->getLevel() === 'info' &&
            strpos($event->getMessage(), '404') !== false) {
            return null;
        }

        return $event;
    },

    // Capture unhandled promise rejections
    'capture_unhandled_rejections' => true,

    // List of exceptions to ignore
    'ignore_exceptions' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    // Breadcrumbs configuration
    'breadcrumbs' => [
        // Capture Laravel logs as breadcrumbs
        'logs' => true,

        // Capture SQL queries as breadcrumbs
        'sql_queries' => true,

        // Capture HTTP client requests as breadcrumbs
        'http_client_requests' => true,

        // Capture cache operations as breadcrumbs
        'cache' => true,

        // Capture queue jobs as breadcrumbs
        'queue_jobs' => true,
    ],

    // User context
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),
];
