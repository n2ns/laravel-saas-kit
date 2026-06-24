<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Return system health status.
     */
    public function __invoke(): JsonResponse
    {
        $startTime = microtime(true);

        // Check database connectivity and measure latency
        $dbStatus = 'ok';
        $dbLatencyMs = 0;

        try {
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $dbStart) * 1000, 2);
        } catch (Exception $e) {
            $dbStatus = 'error';
            $dbLatencyMs = -1;
        }

        // Check cache connectivity
        $cacheStatus = 'ok';
        try {
            Cache::put('health_check', true, 10);
            Cache::get('health_check');
        } catch (Exception $e) {
            $cacheStatus = 'error';
        }

        // Calculate approximate cache hit rate from Laravel's cache stats (if available)
        $cacheHitRate = $this->estimateCacheHitRate();

        $totalLatency = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => ($dbStatus === 'ok' && $cacheStatus === 'ok') ? 'ok' : 'degraded',
            'version' => config('app.version', '2.0.0'),
            'protocol' => 'unified-stateless-v2.0',
            'db_status' => $dbStatus,
            'db_latency_ms' => $dbLatencyMs,
            'cache_status' => $cacheStatus,
            'cache_hit_rate' => $cacheHitRate,
            'response_time_ms' => $totalLatency,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Estimate cache hit rate (simplified).
     *
     * In production, this could be replaced with actual Redis INFO stats.
     */
    private function estimateCacheHitRate(): float
    {
        // For file/array cache drivers, we can't get real stats
        // Return a placeholder that indicates "healthy but unmeasured"
        $driver = config('cache.default');

        if ($driver === 'redis') {
            try {
                $info = Redis::connection()->info('stats');
                $hits = $info['keyspace_hits'] ?? 0;
                $misses = $info['keyspace_misses'] ?? 0;
                $total = $hits + $misses;

                return $total > 0 ? round($hits / $total, 2) : 1.0;
            } catch (Exception $e) {
                return -1;
            }
        }

        // For non-Redis drivers, return 1.0 (assumed healthy)
        return 1.0;
    }
}
