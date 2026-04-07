<?php

namespace Chaton\SDK;

use Chaton\SDK\Exceptions\LicenseException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class PluginClient
{
    protected Client $client;

    protected string $serverUrl;

    public function __construct()
    {
        $this->serverUrl = rtrim(config('chaton-license.server_url'), '/');

        $this->client = new Client([
            'base_uri' => $this->serverUrl,
            'timeout' => config('chaton-license.timeout.request', 30),
            'connect_timeout' => config('chaton-license.timeout.connect', 10),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'ChatonPluginUpdater/1.0',
            ],
        ]);
    }

    /**
     * Check for plugin updates.
     *
     * @param  string  $purchaseCode  License purchase code
     * @param  array   $plugins       Array of ['slug' => string, 'version' => string]
     * @param  string  $domain        Current domain
     */
    public function checkUpdates(string $purchaseCode, array $plugins, string $domain): array
    {
        return $this->request('POST', '/api/plugins/check-updates', [
            'plugins' => $plugins,
            'domain' => $domain,
        ], $purchaseCode);
    }

    /**
     * Request a download token for a plugin.
     *
     * @param  string  $purchaseCode  License purchase code
     * @param  string  $slug          Plugin slug
     * @param  string  $domain        Current domain
     */
    public function requestDownloadToken(string $purchaseCode, string $slug, string $domain): array
    {
        return $this->request('POST', '/api/plugins/download-token', [
            'slug' => $slug,
            'domain' => $domain,
        ], $purchaseCode);
    }

    /**
     * Download a plugin ZIP file.
     *
     * @param  string  $downloadUrl  Full download URL
     * @param  string  $token        JWT download token
     * @param  string  $destination  Local file path to save the ZIP
     */
    public function downloadPlugin(string $downloadUrl, string $token, string $destination): void
    {
        try {
            // Strip base URL to get path (handle both full URL and relative path)
            $path = str_starts_with($downloadUrl, $this->serverUrl)
                ? substr($downloadUrl, strlen($this->serverUrl))
                : $downloadUrl;

            $this->client->request('GET', $path, [
                RequestOptions::HEADERS => [
                    'X-Download-Token' => $token,
                ],
                RequestOptions::SINK => $destination,
            ]);

        } catch (GuzzleException $e) {
            if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $body = (string) $e->getResponse()->getBody();
                $decoded = json_decode($body, true);

                if (isset($decoded['error'])) {
                    throw new \RuntimeException("Download failed: {$decoded['error']} — ".($decoded['message'] ?? ''));
                }
            }

            throw new \RuntimeException('Failed to download plugin: '.$e->getMessage());
        }
    }

    /**
     * Make HTTP request to plugin server with license key header.
     */
    protected function request(string $method, string $endpoint, array $data, string $purchaseCode): array
    {
        try {
            $options = [
                RequestOptions::HEADERS => [
                    'X-License-Key' => $purchaseCode,
                ],
            ];

            if (! empty($data)) {
                $options[RequestOptions::JSON] = $data;
            }

            $response = $this->client->request($method, $endpoint, $options);

            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw LicenseException::serverUnreachable();
            }

            return $decoded;

        } catch (GuzzleException $e) {
            if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $body = (string) $e->getResponse()->getBody();
                $decoded = json_decode($body, true);

                if ($decoded && isset($decoded['message'])) {
                    return $decoded;
                }
            }

            \Log::error('Plugin server connection error', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'server_url' => $this->serverUrl,
            ]);

            throw LicenseException::serverUnreachable();
        }
    }
}
