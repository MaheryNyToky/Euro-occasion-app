<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    /**
     * Check system health and service dependencies.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $status = 'ok';
        $services = [];

        // Check PostgreSQL database
        try {
            DB::connection()->getPdo();
            $services['database'] = [
                'status' => 'connected',
                'driver' => DB::connection()->getDriverName(),
            ];
        } catch (Throwable $e) {
            $status = 'degraded';
            $services['database'] = [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
            ];
        }

        // Check Redis cache / queue
        try {
            Redis::connection()->ping();
            $services['redis'] = [
                'status' => 'connected',
            ];
        } catch (Throwable $e) {
            $status = 'degraded';
            $services['redis'] = [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
            ];
        }

        $httpStatus = $status === 'ok' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'environment' => config('app.env'),
            'timestamp' => now()->toISOString(),
            'request_id' => $request->attributes->get('request_id'),
            'services' => $services,
        ], $httpStatus);
    }
}
