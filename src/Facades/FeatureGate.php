<?php

namespace Chaton\SDK\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled(string $feature)
 * @method static void ensureEnabled(string $feature)
 * @method static bool isSaasEnabled()
 * @method static array getEnabledFeatures()
 * @method static bool hasAny(array $features)
 * @method static bool hasAll(array $features)
 *
 * @see \Egiw\ChatonLicense\FeatureGate
 */
class FeatureGate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Egiw\ChatonLicense\FeatureGate::class;
    }
}
