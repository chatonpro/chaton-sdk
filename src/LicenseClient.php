<?php

namespace Chaton\SDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Chaton\SDK\Exceptions\LicenseException;

class LicenseClient
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
                'User-Agent' => 'ChatonLicenseSDK/1.0',
            ],
        ]);
    }

    /**
     * Activate license with purchase code
     */
    public function activate(string $purchaseCode, string $domain): array
    {
        return $this->request('POST', '/api/activate', [
            'purchase_code' => $purchaseCode,
            'domain' => $domain,
        ]);
    }

    /**
     * Validate existing license
     */
    public function validate(string $purchaseCode, string $domain): array
    {
        return $this->request('POST', '/api/validate', [
            'purchase_code' => $purchaseCode,
            'domain' => $domain,
        ]);
    }

    /**
     * Deactivate license
     */
    public function deactivate(string $purchaseCode, string $domain): array
    {
        return $this->request('POST', '/api/deactivate', [
            'purchase_code' => $purchaseCode,
            'domain' => $domain,
        ]);
    }

    /**
     * Get features for license type
     */
    public function getFeatures(string $licenseType): array
    {
        return $this->request('GET', '/api/features/' . $licenseType);
    }

    /**
     * Make HTTP request to license server
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $options = [];
            
            if (!empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $endpoint, $options);
            
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw LicenseException::serverUnreachable();
            }

            return $decoded;

        } catch (GuzzleException $e) {
            // Check if we have a response body (HTTP error like 409, 400, etc)
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = (string) $response->getBody();
                $decoded = json_decode($body, true);
                
                if ($decoded && isset($decoded['message'])) {
                    // Return the error response so it can be handled properly
                    return $decoded;
                }
            }
            
            \Log::error('License server connection error', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'server_url' => $this->serverUrl,
            ]);
            throw LicenseException::serverUnreachable();
        }
    }
}
