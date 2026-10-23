<?php

namespace App\Security;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Ověření ID tokenu (OpenID Connect) od externího poskytovatele přihlášení.
 *
 * Kontroluje podpis (klíč podle „kid“ z veřejné sady klíčů poskytovatele), expiraci, vydavatele (iss)
 * a příjemce (aud = naše client ID). Client ID může být víc (web, iOS aplikace…), oddělené čárkou.
 */
abstract class OidcIdTokenVerifier
{
    /** @var list<string> */
    private array $clientIds;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        ?string $clientIds,
    ) {
        $this->clientIds = array_values(array_filter(array_map('trim', explode(',', (string) $clientIds))));
    }

    abstract protected function jwksUrl(): string;

    /**
     * @return list<string>
     */
    abstract protected function issuers(): array;

    public function isConfigured(): bool
    {
        return $this->clientIds !== [];
    }

    /**
     * @return array<string, mixed> claims tokenu
     *
     * @throws InvalidIdTokenException
     */
    public function verify(string $idToken): array
    {
        if (!$this->isConfigured()) {
            throw new InvalidIdTokenException('Provider is not configured');
        }

        try {
            $claims = (array) JWT::decode($idToken, JWK::parseKeySet($this->fetchJwks(), 'RS256'));
        } catch (\Throwable $e) {
            throw new InvalidIdTokenException('Invalid token signature or expired token', previous: $e);
        }

        if (!in_array($claims['iss'] ?? null, $this->issuers(), true)) {
            throw new InvalidIdTokenException('Invalid issuer');
        }

        if (!in_array($claims['aud'] ?? null, $this->clientIds, true)) {
            throw new InvalidIdTokenException('Invalid audience');
        }

        if (!is_string($claims['sub'] ?? null) || $claims['sub'] === '') {
            throw new InvalidIdTokenException('Missing subject');
        }

        return $claims;
    }

    /**
     * Veřejné klíče poskytovatele, cache 1 h (poskytovatelé je rotují zřídka).
     *
     * @return array<string, mixed>
     */
    private function fetchJwks(): array
    {
        return $this->cache->get('oidc_jwks_'.md5($this->jwksUrl()), function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return $this->httpClient->request('GET', $this->jwksUrl())->toArray();
        });
    }
}
