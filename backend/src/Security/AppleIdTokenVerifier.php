<?php

namespace App\Security;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppleIdTokenVerifier
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $appleClientId // z configu
    ) {}

    public function verify(string $idToken): array
    {
        $keys = $this->getApplePublicKeys();

        $decoded = null;
        foreach ($keys as $key) {
            try {
                $decoded = JWT::decode(
                    $idToken,
                    new Key($key['publicKey'], 'RS256')
                );
                break;
            } catch (\Exception) {}
        }

        if (!$decoded) {
            throw new \RuntimeException('Invalid Apple token');
        }

        // Povinné kontroly
        if ($decoded->iss !== 'https://appleid.apple.com') {
            throw new \RuntimeException('Invalid issuer');
        }

        if ($decoded->aud !== $this->appleClientId) {
            throw new \RuntimeException('Invalid audience');
        }

        return (array) $decoded;
    }

    private function getApplePublicKeys(): array
    {
        $response = $this->httpClient->request(
            'GET',
            'https://appleid.apple.com/auth/keys'
        );

        $jwks = $response->toArray();

        $keys = [];
        foreach ($jwks['keys'] as $jwk) {
            $keys[] = [
                'publicKey' => JWK::parseKey([
                    'kty' => $jwk['kty'],
                    'n' => $jwk['n'],
                    'e' => $jwk['e'],
                ])
            ];
        }

        return $keys;
    }
}
