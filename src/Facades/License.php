<?php

namespace Chaton\SDK\Facades;

use Chaton\SDK\Contracts\LicenseInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array activate(string $purchaseCode, string $domain)
 * @method static array validate(bool $forceRemote = false)
 * @method static array deactivate()
 * @method static bool isValid()
 * @method static string|null getLicenseType()
 * @method static bool isSaasEnabled()
 * @method static bool hasFeature(string $feature)
 * @method static array getFeatures()
 * @method static array|null getLicenseInfo()
 *
 * @see \Chaton\SDK\LicenseManager
 */
class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LicenseInterface::class;
    }
}
