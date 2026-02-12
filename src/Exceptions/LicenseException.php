<?php

namespace Chaton\SDK\Exceptions;

use Exception;

class LicenseException extends Exception
{
    public static function invalidSignature(): self
    {
        return new self('License signature verification failed. Response may be tampered.');
    }

    public static function serverUnreachable(): self
    {
        return new self('License server is unreachable. Please check your internet connection.');
    }

    public static function invalidPurchaseCode(): self
    {
        return new self('Invalid purchase code. Please check your purchase code and try again.');
    }

    public static function domainMismatch(): self
    {
        return new self('License is activated for a different domain.');
    }

    public static function licenseExpired(): self
    {
        return new self('License has expired. Please renew your license.');
    }

    public static function licenseNotActivated(): self
    {
        return new self('License is not activated. Please activate your license first.');
    }

    public static function featureNotAvailable(string $feature): self
    {
        return new self("Feature '{$feature}' is not available for your license type.");
    }

    public static function gracePeriodExpired(): self
    {
        return new self('Grace period has expired. License validation required.');
    }

    public static function domainAlreadyActivated(): self
    {
        return new self('This purchase code is already activated on another domain.');
    }
}
