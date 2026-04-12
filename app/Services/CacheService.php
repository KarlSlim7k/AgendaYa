<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Invalidar caché del dashboard cuando cambian datos
     */
    public static function invalidateDashboardCache(int $businessId): void
    {
        $periods = ['today', 'week', 'month', 'year'];
        foreach ($periods as $period) {
            Cache::forget("business.dashboard.{$businessId}.{$period}");
        }
    }

    /**
     * Invalidar caché de listas cuando cambian datos
     */
    public static function invalidateListCache(int $businessId, string $listType): void
    {
        Cache::forget("business.{$listType}.{$businessId}");
    }

    /**
     * Obtener o almacenar en caché
     */
    public static function remember(string $key, int $seconds, callable $callback)
    {
        return Cache::remember($key, $seconds, $callback);
    }
}
