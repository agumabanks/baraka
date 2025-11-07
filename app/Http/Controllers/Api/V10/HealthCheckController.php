<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'cache' => $this->checkCache(),
        ];

        $status = collect($checks)->every(fn ($check) => ($check['status'] ?? 'error') === 'ok')
            ? 'ok'
            : 'degraded';

        return response()->json([
            'success' => $status === 'ok',
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'checks' => $checks,
        ], $status === 'ok' ? 200 : 503);
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'status' => 'ok',
                'driver' => config('database.default'),
            ];
        } catch (Throwable $exception) {
            Log::error('Health check database failure', ['message' => $exception->getMessage()]);

            return [
                'status' => 'error',
                'message' => 'Database connection failed',
            ];
        }
    }

    protected function checkStorage(): array
    {
        $diskName = config('filesystems.default');

        try {
            $disk = Storage::disk($diskName);
            $path = 'healthchecks/'.uniqid('ping_', true).'.txt';
            $disk->put($path, 'ok');
            $disk->delete($path);

            return [
                'status' => 'ok',
                'disk' => $diskName,
            ];
        } catch (Throwable $exception) {
            Log::error('Health check storage failure', ['message' => $exception->getMessage(), 'disk' => $diskName]);

            return [
                'status' => 'error',
                'message' => 'Storage disk not writable',
                'disk' => $diskName,
            ];
        }
    }

    protected function checkQueue(): array
    {
        $connection = config('queue.default');

        try {
            Queue::connection($connection);

            return [
                'status' => 'ok',
                'driver' => $connection,
            ];
        } catch (Throwable $exception) {
            Log::error('Health check queue failure', ['message' => $exception->getMessage(), 'connection' => $connection]);

            return [
                'status' => 'error',
                'message' => 'Queue connection failed',
                'driver' => $connection,
            ];
        }
    }

    protected function checkCache(): array
    {
        $store = config('cache.default');
        $key = 'healthcheck:ping:'.uniqid('', true);

        try {
            Cache::store($store)->put($key, 'ok', 5);
            Cache::store($store)->forget($key);

            return [
                'status' => 'ok',
                'store' => $store,
            ];
        } catch (Throwable $exception) {
            Log::error('Health check cache failure', ['message' => $exception->getMessage(), 'store' => $store]);

            return [
                'status' => 'error',
                'message' => 'Cache store not available',
                'store' => $store,
            ];
        }
    }
}
