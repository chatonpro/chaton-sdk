<?php

namespace Chaton\SDK\Contracts;

interface LicenseInterface
{
    /**
     * Activate a license with purchase code
     */
    public function activate(string $purchaseCode, string $domain): array;

    /**
     * Validate current license
     */
    public function validate(bool $forceRemote = false): array;

    /**
     * Deactivate current license
     */
    public function deactivate(): array;

    /**
     * Check if license is valid
     */
    public function isValid(): bool;

    /**
     * Get license type (regular or extended)
     */
    public function getLicenseType(): ?string;

    /**
     * Check if SAAS features are enabled
     */
    public function isSaasEnabled(): bool;

    /**
     * Get all available features for current license
     */
    public function getFeatures(): array;

    /**
     * Get license information
     */
    public function getLicenseInfo(): ?array;
}
