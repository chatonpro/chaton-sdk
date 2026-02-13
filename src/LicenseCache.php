<?php

namespace Chaton\SDK;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LicenseCache
{
    protected string $cacheKey;

    protected int $ttl;

    protected string $driver;

    public function __construct()
    {
        $this->cacheKey = config('chaton-license.cache.key_prefix', 'chaton_license_');
        $this->ttl = config('chaton-license.cache.ttl', 86400);
        // Always use database driver to avoid file permission issues
        $this->driver = 'database';
    }

    /**
     * Store license data in cache
     */
    public function store(array $licenseData): void
    {
        $data = array_merge($licenseData, [
            'cached_at' => Carbon::now()->toIso8601String(),
        ]);

        $payload = [
            'data' => $data,
            'signature' => $this->generateSignature($data),
            'stored_at' => time(),
        ];

        Cache::driver($this->driver)->put(
            $this->cacheKey.'data',
            $payload,
            $this->ttl
        );
    }

    /**
     * Get cached license data
     */
    public function get(): ?array
    {
        $cached = Cache::driver($this->driver)->get($this->cacheKey.'data');

        if (!$cached || !is_array($cached)) {
            return null;
        }

        if (!isset($cached['signature']) || !isset($cached['data'])) {
            return $cached;
        }

        $expectedSignature = $this->generateSignature($cached['data']);

        if (!hash_equals($cached['signature'], $expectedSignature)) {
            \Illuminate\Support\Facades\Log::notice('License cache signature mismatch - revalidating');
            
            $this->clear();
            
            try {
                $manager = app(\Chaton\SDK\Contracts\LicenseInterface::class);
                $manager->validate(force: true);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-revalidation failed', ['error' => $e->getMessage()]);
            }
            
            return null;
        }

        if (isset($cached['stored_at']) && $cached['stored_at'] < (time() - $this->ttl)) {
            return null;
        }

        return $cached['data'];
    }

    /**
     * Check if cache is still valid (within grace period)
     */
    public function isWithinGracePeriod(): bool
    {
        $data = $this->get();

        if (! $data || ! isset($data['cached_at'])) {
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
        Cache::driver($this->driver)->forget($this->cacheKey.'data');
    }

    /**
     * Store last validation timestamp
     */
    public function storeLastValidation(): void
    {
        Cache::driver($this->driver)->put(
            $this->cacheKey.'last_validation',
            Carbon::now()->toIso8601String(),
            $this->ttl
        );
    }

    /**
     * Get last validation timestamp
     */
    public function getLastValidation(): ?Carbon
    {
        $timestamp = Cache::driver($this->driver)->get($this->cacheKey.'last_validation');

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * Check if daily validation is needed
     */
    public function needsDailyValidation(): bool
    {
        $lastValidation = $this->getLastValidation();

        if (! $lastValidation) {
            return true;
        }

        return $lastValidation->diffInHours(Carbon::now()) >= 24;
    }

    protected function generateSignature(array $data): string
    {
        $appKey = config('app.key');
        
        if (!$appKey) {
            throw new \RuntimeException('Application key not set');
        }

        return hash_hmac('sha256', json_encode($data), $appKey);
    }
}
