<?php

namespace Chaton\SDK\Facades;

use Chaton\SDK\Contracts\PluginUpdaterInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array  checkUpdates(array $plugins)
 * @method static array  requestDownloadToken(string $slug)
 * @method static string downloadPlugin(string $downloadUrl, string $token, string $destination)
 * @method static string downloadAndVerify(string $slug, string $destDir)
 *
 * @see \Chaton\SDK\PluginUpdateChecker
 */
class PluginUpdater extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PluginUpdaterInterface::class;
    }
}
