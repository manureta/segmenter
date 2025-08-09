<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache keys configuration
     */
    const KEYS = [
        'LOCALIDAD_SVG' => 'localidad_svg_%s',
        'LOCALIDAD_RADIOS' => 'localidad_radios_%s',
        'PROVINCIA_JSON' => 'provincia_json_%s',
        'SEGMENTACION_RESULT' => 'segmentacion_%s_%s',
        'DEPARTAMENTO_LIST' => 'departamento_list_%s',
    ];

    /**
     * Cache TTL configuration (in seconds)
     */
    const TTL = [
        'SHORT' => 300,    // 5 minutes
        'MEDIUM' => 1800,  // 30 minutes  
        'LONG' => 3600,    // 1 hour
        'EXTENDED' => 86400, // 24 hours
    ];

    /**
     * Get cached data or execute callback
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache operation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // Fallback: execute callback directly
            return $callback();
        }
    }

    /**
     * Generate cache key
     */
    public function key(string $template, ...$params): string
    {
        return sprintf(self::KEYS[$template], ...$params);
    }

    /**
     * Invalidate cache pattern
     */
    public function forgetPattern(string $pattern): void
    {
        try {
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } catch (\Exception $e) {
            Log::warning('Cache invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache localidad SVG
     */
    public function cacheLocalidadSvg(string $codigo, callable $generator)
    {
        $key = $this->key('LOCALIDAD_SVG', $codigo);
        return $this->remember($key, self::TTL['EXTENDED'], $generator);
    }

    /**
     * Cache localidad radios
     */
    public function cacheLocalidadRadios(string $codigo, callable $generator)
    {
        $key = $this->key('LOCALIDAD_RADIOS', $codigo);
        return $this->remember($key, self::TTL['LONG'], $generator);
    }

    /**
     * Cache provincia geojson
     */
    public function cacheProvinciaJson(string $codigo, callable $generator)
    {
        $key = $this->key('PROVINCIA_JSON', $codigo);
        return $this->remember($key, self::TTL['MEDIUM'], $generator);
    }

    /**
     * Invalidate localidad related cache
     */
    public function invalidateLocalidad(string $codigo): void
    {
        $patterns = [
            sprintf(self::KEYS['LOCALIDAD_SVG'], $codigo),
            sprintf(self::KEYS['LOCALIDAD_RADIOS'], $codigo),
            sprintf(self::KEYS['SEGMENTACION_RESULT'], $codigo, '*'),
        ];

        foreach ($patterns as $pattern) {
            $this->forgetPattern($pattern);
        }
    }
}