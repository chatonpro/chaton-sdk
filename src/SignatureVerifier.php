<?php

namespace Chaton\SDK;

use Chaton\SDK\Exceptions\InvalidSignatureException;

class SignatureVerifier
{
    /**
     * Verify RSA signature of the response data
     */
    public function verify(array $data, string $signature, string $publicKey): bool
    {
        try {
            // Convert data to JSON string (canonical form)
            $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Decode base64 signature
            $signatureBinary = base64_decode($signature);

            if ($signatureBinary === false) {
                return false;
            }

            // Get public key resource
            $publicKeyResource = openssl_pkey_get_public($publicKey);

            if ($publicKeyResource === false) {
                return false;
            }

            // Verify signature using SHA256
            $result = openssl_verify(
                $payload,
                $signatureBinary,
                $publicKeyResource,
                OPENSSL_ALGO_SHA256
            );

            return $result === 1;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verify and extract data from signed response
     */
    public function verifyAndExtract(array $response, string $publicKey): array
    {
        if (! isset($response['data']) || ! isset($response['signature'])) {
            throw InvalidSignatureException::invalidSignature();
        }

        $verified = $this->verify($response['data'], $response['signature'], $publicKey);

        if (! $verified) {
            throw InvalidSignatureException::invalidSignature();
        }

        return $response['data'];
    }

    /**
     * Generate RSA key pair (for license server setup)
     * This is a helper method, not used in SDK runtime
     */
    public static function generateKeyPair(): array
    {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if ($resource === false) {
            throw new \RuntimeException('Failed to generate RSA key pair');
        }

        // Export private key
        openssl_pkey_export($resource, $privateKey);

        // Export public key
        $publicKeyDetails = openssl_pkey_get_details($resource);
        $publicKey = $publicKeyDetails['key'];

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
        ];
    }
}
