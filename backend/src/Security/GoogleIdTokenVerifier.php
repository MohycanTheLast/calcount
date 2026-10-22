<?php

namespace App\Security;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleIdTokenVerifier
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $googleClientId
    ) {}

    public function verify(string $idToken): array
    {
        $certs = $this->httpClient
            ->request('GET', 'https://www.googleapis.com/oauth2/v3/certs')
            ->toArray();

        foreach ($certs['keys'] as $key) {
            try {
                $decoded = JWT::decode(
                    $idToken,
                    new Key(
                        JWK::parseKey([
                            'kty' => $key['kty'],
                            'n' => $key['n'],
                            'e' => $key['e'],
                        ]),
                        'RS256'
                    )
                );

                if ($decoded->aud !== $this->googleClientId) {
                    throw new \RuntimeException();
                }

                return (array) $decoded;
            } catch (\Exception) {}
        }

        throw new \RuntimeException('Invalid Google token');
    }
}
