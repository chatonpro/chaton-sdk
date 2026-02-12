<?php

namespace Chaton\SDK\Facades;

use Illuminate\Support\Facades\Facade;
use Chaton\SDK\Contracts\LicenseInterface;

/**
 * @method static array activate(string $purchaseCode, string $domain)
 * @method static array validate(bool $forceRemote = false)
 * @method static array deactivate()
 * @method static bool isValid()
 * @method static string|null getLicenseType()
 * @method static bool isSaasEnabled()
 * @method static array getFeatures()
 * @method static array|null getLicenseInfo()
 *
 * @see \Egiw\ChatonLicense\LicenseManager
 */
class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LicenseInterface::class;
    }
}
