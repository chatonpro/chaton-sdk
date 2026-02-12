<?php

namespace Chaton\SDK;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LicenseCache
{
    protected string $cacheKey;
    protected int $ttl;
    protected string $driver;

    public function __construct()
    {
        $this->cacheKey = config('chaton-license.cache.key_prefix', 'chaton_license_');
        $this->ttl = config('chaton-license.cache.ttl', 86400);
        $this->driver = config('chaton-license.cache.driver', 'redis');
    }

    /**
     * Store license data in cache
     */
    public function store(array $licenseData): void
    {
        $data = array_merge($licenseData, [
            'cached_at' => Carbon::now()->toIso8601String(),
        ]);

        Cache::driver($this->driver)->put(
            $this->cacheKey . 'data',
            $data,
            $this->ttl
        );
    }

    /**
     * Get cached license data
     */
    public function get(): ?array
    {
        return Cache::driver($this->driver)->get($this->cacheKey . 'data');
    }

    /**
     * Check if cache is still valid (within grace period)
     */
    public function isWithinGracePeriod(): bool
    {
        $data = $this->get();

        if (!$data || !isset($data['cached_at'])) {
            return false;
        }

        $cachedAt = Carbon::parse($data['cached_at']);
        $gracePeriodDays = config('chaton-license.grace_period_days', 7);
        $expiresAt = $cachedAt->addDays($gracePeriodDays);

        return Carbon::now()->lessThan($expiresAt);
    }

    /**
     * Clear license cache
     */
    public function clear(): void
    {
        Cache::driver($this->driver)->forget($this->cacheKey . 'data');
    }

    /**
     * Store last validation timestamp
     */
    public function storeLastValidation(): void
    {
        Cache::driver($this->driver)->put(
            $this->cacheKey . 'last_validation',
            Carbon::now()->toIso8601String(),
            $this->ttl
        );
    }

    /**
     * Get last validation timestamp
     */
    public function getLastValidation(): ?Carbon
    {
        $timestamp = Cache::driver($this->driver)->get($this->cacheKey . 'last_validation');

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * Check if daily validation is needed
     */
    public function needsDailyValidation(): bool
    {
        $lastValidation = $this->getLastValidation();

        if (!$lastValidation) {
            return true;
        }

        return $lastValidation->diffInHours(Carbon::now()) >= 24;
    }
}
