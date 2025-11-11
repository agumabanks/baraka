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

    // Attach stack traces to all messages.
    'attach_stacktrace' => true,

    // Set the environment
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    // Release version
    'release' => env('SENTRY_RELEASE', env('APP_VERSION')),

    // Error filtering
    'before_send' => null,

    // List of exceptions to ignore
    'ignore_exceptions' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    // User context
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),
];
