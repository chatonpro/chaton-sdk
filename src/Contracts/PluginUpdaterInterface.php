<?php

namespace Chaton\SDK\Contracts;

interface PluginUpdaterInterface
{
    /**
     * Check for available updates for a list of plugins.
     *
     * @param  array  $plugins  Array of ['slug' => string, 'version' => string]
     * @return array  Map of slug => update info
     */
    public function checkUpdates(array $plugins): array;

    /**
     * Request a signed download token for a plugin.
     *
     * @param  string  $slug  Plugin slug
     * @return array  Token response data
     */
    public function requestDownloadToken(string $slug): array;

    /**
     * Download a plugin ZIP file to a local path.
     *
     * @param  string  $downloadUrl  Full download URL
     * @param  string  $token        JWT download token
     * @param  string  $destination  Local file path to save the ZIP
     * @return string  Path to downloaded file
     */
    public function downloadPlugin(string $downloadUrl, string $token, string $destination): string;

    /**
     * Full update flow: check token → download → verify checksum.
     * Returns the local path to the verified ZIP file.
     *
     * @param  string  $slug     Plugin slug
     * @param  string  $destDir  Directory to save the downloaded ZIP
     * @return string  Path to the verified ZIP file
     */
    public function downloadAndVerify(string $slug, string $destDir): string;
}
