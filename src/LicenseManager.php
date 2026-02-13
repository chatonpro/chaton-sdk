<?php

namespace Chaton\SDK;

use Chaton\SDK\Contracts\LicenseInterface;
use Chaton\SDK\Exceptions\LicenseException;
use Illuminate\Support\Facades\Log;

class LicenseManager implements LicenseInterface
{
    protected LicenseClient $client;

    protected LicenseCache $cache;

    protected SignatureVerifier $verifier;

    protected string $publicKey;

    public function __construct(
        LicenseClient $client,
        LicenseCache $cache,
        SignatureVerifier $verifier
    ) {
        $this->client = $client;
        $this->cache = $cache;
        $this->verifier = $verifier;
        $this->publicKey = config('chaton-license.public_key');
    }

    /**
     * Activate a license with purchase code
     */
    public function activate(string $purchaseCode, string $domain): array
    {
        try {
            $response = $this->client->activate($purchaseCode, $domain);

            $data = $this->verifier->verifyAndExtract($response, $this->publicKey);

            if (! $data['success']) {
                throw new LicenseException($data['message'] ?? 'Activation failed');
            }

            $licenseData = [
                'purchase_code' => $purchaseCode,
                'domain' => $domain,
                'license_type' => $data['license_type'],
                'features' => $data['features'] ?? [],
                'activated_at' => $data['activated_at'] ?? now()->toIso8601String(),
                'valid' => true,
            ];

            $this->cache->store($licenseData);
            $this->cache->storeLastValidation();

            Log::info('License activated successfully', [
                'domain' => $domain,
                'license_type' => $data['license_type'],
            ]);

            return [
                'success' => true,
                'license_type' => $data['license_type'],
                'features' => $data['features'] ?? [],
            ];

        } catch (LicenseException $e) {
            Log::error('License activation failed', [
                'error' => $e->getMessage(),
                'domain' => $domain,
            ]);
            throw $e;
        }
    }

    /**
     * Validate current license
     */
    public function validate(bool $forceRemote = false): array
    {
        $cachedData = $this->cache->get();

        if (! $cachedData) {
            throw LicenseException::licenseNotActivated();
        }

        $needsValidation = $forceRemote || $this->cache->needsDailyValidation();

        if (! $needsValidation) {
            return [
                'success' => true,
                'source' => 'cache',
                'license_type' => $cachedData['license_type'],
                'features' => $cachedData['features'] ?? [],
            ];
        }

        try {
            $response = $this->client->validate(
                $cachedData['purchase_code'],
                $cachedData['domain']
            );

            $data = $this->verifier->verifyAndExtract($response, $this->publicKey);

            if (! $data['success']) {
                throw new LicenseException($data['message'] ?? 'Validation failed');
            }

            $licenseData = [
                'purchase_code' => $cachedData['purchase_code'],
                'domain' => $cachedData['domain'],
                'license_type' => $data['license_type'],
                'features' => $data['features'] ?? [],
                'activated_at' => $cachedData['activated_at'],
                'valid' => true,
            ];

            $this->cache->store($licenseData);
            $this->cache->storeLastValidation();

            return [
                'success' => true,
                'source' => 'remote',
                'license_type' => $data['license_type'],
                'features' => $data['features'] ?? [],
            ];

        } catch (LicenseException $e) {
            if ($this->cache->isWithinGracePeriod()) {
                Log::warning('License server unreachable, using cached data within grace period', [
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => true,
                    'source' => 'cache_grace',
                    'license_type' => $cachedData['license_type'],
                    'features' => $cachedData['features'] ?? [],
                ];
            }

            Log::error('License validation failed and grace period expired', [
                'error' => $e->getMessage(),
            ]);

            throw LicenseException::gracePeriodExpired();
        }
    }

    /**
     * Deactivate current license
     */
    public function deactivate(): array
    {
        $cachedData = $this->cache->get();

        if (! $cachedData) {
            throw LicenseException::licenseNotActivated();
        }

        try {
            $response = $this->client->deactivate(
                $cachedData['purchase_code'],
                $cachedData['domain']
            );

            $data = $this->verifier->verifyAndExtract($response, $this->publicKey);

            $this->cache->clear();

            Log::info('License deactivated successfully', [
                'domain' => $cachedData['domain'],
            ]);

            return [
                'success' => true,
                'message' => 'License deactivated successfully',
            ];

        } catch (LicenseException $e) {
            Log::error('License deactivation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if license is valid
     */
    public function isValid(): bool
    {
        try {
            $result = $this->validate();

            return $result['success'] ?? false;
        } catch (LicenseException $e) {
            return false;
        }
    }

    /**
     * Get license type (regular or extended)
     */
    public function getLicenseType(): ?string
    {
        $cachedData = $this->cache->get();

        return $cachedData['license_type'] ?? null;
    }

    /**
     * Check if SAAS features are enabled
     */
    public function isSaasEnabled(): bool
    {
        return $this->hasFeature('organizations') 
            || $this->hasFeature('subscription_plans')
            || $this->hasFeature('billing');
    }

    /**
     * Check if specific feature is enabled
     */
    public function hasFeature(string $feature): bool
    {
        $licenseType = $this->getLicenseType();

        if (! $licenseType) {
            return false;
        }

        $cachedData = $this->cache->get();
        $features = $cachedData['features'] ?? [];

        if (isset($features[$feature])) {
            return $features[$feature] === true;
        }

        if ($feature === 'organizations' || $feature === 'subscription_plans' || $feature === 'billing') {
            if (isset($features['saas'])) {
                return $features['saas'] === true;
            }
        }

        return config("chaton-license.features.{$feature}.{$licenseType}", false);
    }

    /**
     * Get all available features for current license
     */
    public function getFeatures(): array
    {
        $cachedData = $this->cache->get();

        return $cachedData['features'] ?? [];
    }

    /**
     * Get license information
     */
    public function getLicenseInfo(): ?array
    {
        return $this->cache->get();
    }

    /**
     * Get current domain
     */
    protected function getCurrentDomain(): string
    {
        $configDomain = config('chaton-license.domain');

        if ($configDomain) {
            return $configDomain;
        }

        if (app()->runningInConsole()) {
            return parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        }

        return request()->getHost();
    }
}
