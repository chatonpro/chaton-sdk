<?php

namespace Chaton\SDK;

use Chaton\SDK\Contracts\LicenseInterface;
use Chaton\SDK\Contracts\PluginUpdaterInterface;
use Chaton\SDK\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Log;

class PluginUpdateChecker implements PluginUpdaterInterface
{
    public function __construct(
        protected PluginClient $client,
        protected SignatureVerifier $verifier,
        protected LicenseInterface $license,
    ) {}

    /**
     * Check for available updates for a list of plugins.
     *
     * @param  array  $plugins  Array of ['slug' => string, 'version' => string]
     * @return array  Map of slug => update info
     *
     * Example return:
     * [
     *   'telegram' => [
     *     'has_update'   => true,
     *     'version'      => '1.1.0',
     *     'changelog'    => '- Bug fixes',
     *     'download_url' => 'https://api.chaton.pro/downloads/telegram-1.1.0.zip',
     *     'checksum'     => 'sha256hex...',
     *   ],
     *   'instagram' => ['has_update' => false, 'version' => '1.0.0', ...],
     * ]
     */
    public function checkUpdates(array $plugins): array
    {
        $purchaseCode = $this->getPurchaseCode();
        $domain = $this->getCurrentDomain();

        $rawResponse = $this->client->checkUpdates($purchaseCode, $plugins, $domain);

        // Verify RSA signature — same mechanism as license responses
        $data = $this->verifyPluginServerResponse($rawResponse);

        return $data['plugins'] ?? [];
    }

    /**
     * Request a signed download token for a plugin.
     *
     * @param  string  $slug  Plugin slug
     * @return array  Token response: token, expires_at, download_url, checksum, filename
     */
    public function requestDownloadToken(string $slug): array
    {
        $purchaseCode = $this->getPurchaseCode();
        $domain = $this->getCurrentDomain();

        $rawResponse = $this->client->requestDownloadToken($purchaseCode, $slug, $domain);

        // Verify RSA signature
        $data = $this->verifyPluginServerResponse($rawResponse);

        // checksum wajib ada — ChatOn akan throw error jika tidak ada
        if (empty($data['checksum'])) {
            throw new \RuntimeException(
                "Plugin '{$slug}' download token is missing checksum. Cannot proceed with installation."
            );
        }

        return $data;
    }

    /**
     * Download a plugin ZIP file to a local path.
     *
     * @param  string  $downloadUrl  Full download URL from token response
     * @param  string  $token        JWT download token
     * @param  string  $destination  Local file path to save the ZIP
     * @return string  Path to downloaded file
     */
    public function downloadPlugin(string $downloadUrl, string $token, string $destination): string
    {
        // Ensure destination directory exists
        $dir = dirname($destination);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->client->downloadPlugin($downloadUrl, $token, $destination);

        if (! file_exists($destination) || filesize($destination) === 0) {
            throw new \RuntimeException("Downloaded file is empty or missing: {$destination}");
        }

        return $destination;
    }

    /**
     * Full update flow: request token → download → verify SHA-256 checksum.
     * Returns the local path to the verified ZIP file.
     *
     * @param  string  $slug     Plugin slug
     * @param  string  $destDir  Directory to save the downloaded ZIP
     * @return string  Path to the verified ZIP file
     *
     * @throws \RuntimeException  If download or checksum verification fails
     * @throws InvalidSignatureException  If server response signature is invalid
     */
    public function downloadAndVerify(string $slug, string $destDir): string
    {
        // Step 1: Request download token (RSA signed)
        $tokenData = $this->requestDownloadToken($slug);

        $token       = $tokenData['token'];
        $downloadUrl = $tokenData['download_url'];
        $checksum    = $tokenData['checksum'];
        $filename    = $tokenData['filename'];

        // Step 2: Download the ZIP file
        $destination = rtrim($destDir, '/').'/'.$filename;
        $this->downloadPlugin($downloadUrl, $token, $destination);

        // Step 3: Verify SHA-256 checksum
        $actualChecksum = hash_file('sha256', $destination);

        if (! hash_equals($checksum, $actualChecksum)) {
            // Remove corrupted file
            @unlink($destination);

            throw new \RuntimeException(
                "Checksum verification failed for plugin '{$slug}'. ".
                "Expected: {$checksum}, Got: {$actualChecksum}. ".
                "File has been removed."
            );
        }

        Log::info("Plugin '{$slug}' downloaded and verified successfully.", [
            'slug'        => $slug,
            'filename'    => $filename,
            'checksum'    => $checksum,
            'destination' => $destination,
        ]);

        return $destination;
    }

    /**
     * Verify RSA signature of plugin server response.
     * Uses the SAME public key and SignatureVerifier as license responses.
     */
    protected function verifyPluginServerResponse(array $rawResponse): array
    {
        $publicKey = config('chaton-license.public_key');

        // Signed response: { "data": {...}, "signature": "base64RSA" }
        if (isset($rawResponse['data'], $rawResponse['signature'])) {
            return $this->verifier->verifyAndExtract($rawResponse, $publicKey);
        }

        // Unsigned response: log warning & pass-through
        // (masa transisi sebelum semua plugin routes live)
        Log::warning('Plugin server response is not signed — skipping verification', [
            'response_keys' => array_keys($rawResponse),
        ]);

        return $rawResponse;
    }

    /**
     * Get the purchase code from cached license info.
     */
    protected function getPurchaseCode(): string
    {
        $info = $this->license->getLicenseInfo();

        if (! $info || empty($info['purchase_code'])) {
            throw new \RuntimeException(
                'Cannot check plugin updates: license is not activated.'
            );
        }

        return $info['purchase_code'];
    }

    /**
     * Get the current domain.
     */
    protected function getCurrentDomain(): string
    {
        $configured = config('chaton-license.domain');

        if ($configured) {
            return $configured;
        }

        return request()->getHost();
    }
}
