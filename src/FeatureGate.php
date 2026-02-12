<?php

namespace Chaton\SDK;

use Chaton\SDK\Contracts\LicenseInterface;
use Chaton\SDK\Exceptions\LicenseException;

class FeatureGate
{
    protected LicenseInterface $license;

    public function __construct(LicenseInterface $license)
    {
        $this->license = $license;
    }

    /**
     * Check if a feature is enabled
     */
    public function isEnabled(string $feature): bool
    {
        if (!$this->license->isValid()) {
            return false;
        }

        $features = $this->license->getFeatures();

        // Check from license server response
        if (isset($features[$feature])) {
            return $features[$feature] === true;
        }

        // Fallback to config-based feature mapping
        $licenseType = $this->license->getLicenseType();

        if (!$licenseType) {
            return false;
        }

        return config("chaton-license.features.{$feature}.{$licenseType}", false);
    }

    /**
     * Ensure a feature is enabled (throw exception if not)
     */
    public function ensureEnabled(string $feature): void
    {
        if (!$this->isEnabled($feature)) {
            throw LicenseException::featureNotAvailable($feature);
        }
    }

    /**
     * Check if SAAS is enabled
     */
    public function isSaasEnabled(): bool
    {
        return $this->isEnabled('saas');
    }

    /**
     * Get all enabled features
     */
    public function getEnabledFeatures(): array
    {
        if (!$this->license->isValid()) {
            return [];
        }

        $features = $this->license->getFeatures();
        $enabled = [];

        foreach ($features as $feature => $status) {
            if ($status === true) {
                $enabled[] = $feature;
            }
        }

        return $enabled;
    }

    /**
     * Check multiple features at once
     */
    public function hasAny(array $features): bool
    {
        foreach ($features as $feature) {
            if ($this->isEnabled($feature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all features are enabled
     */
    public function hasAll(array $features): bool
    {
        foreach ($features as $feature) {
            if (!$this->isEnabled($feature)) {
                return false;
            }
        }

        return true;
    }
}
