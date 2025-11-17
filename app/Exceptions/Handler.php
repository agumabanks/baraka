<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    // public function register()
    // {

    // }

    public function report(Throwable $e)
    {
        try {
            $logPath = storage_path('logs/exception-debug.log');
            $context = [
                'timestamp' => now()->toDateTimeString(),
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl() ?? null,
            ];

            file_put_contents(
                $logPath,
                json_encode($context, JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND
            );
        } catch (Throwable $loggingError) {
            // Swallow any logging errors to avoid interfering with normal reporting.
        }

        parent::report($e);
    }

    public function render($request, Throwable $e)
    {
        if ($this->isHttpException($e)) {
            $status = $e->getStatusCode();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: (Response::$statusTexts[$status] ?? 'Http Error'),
                ], $status);
            }

            if ($status == 401) {
                return response()->view('errors.401', [], 401);
            } elseif ($status == 404) {
                return response()->view('errors.404', [], 404);
            } elseif ($status == 403) {
                return response()->view('errors.403', [], 403);
            } elseif ($status == 405) {
                return response()->view('errors.405', [], 405);
            } elseif ($status == 419) {
                return response()->view('errors.419', [], 419);
            } elseif ($status == 429) {
                return response()->view('errors.429', [], 429);
            } elseif ($status == 500) {
                return response()->view('errors.500', [], 500);
            }
        } else {
            return parent::render($request, $e);
        }

        return response()->view('errors.500', [], 500);
    }
}
