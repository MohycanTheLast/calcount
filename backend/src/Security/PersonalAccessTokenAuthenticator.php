<?php

namespace App\Security;

use App\Entity\Security\PersonalAccessToken;
use App\Repository\PersonalAccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * Přihlášení osobním API tokenem (hlavička „Authorization: Bearer cc_pat_…“).
 *
 * Běží ve vlastním firewallu `personal_token` (security.yaml) – ten se použije jen pro /api požadavky s takovou
 * hlavičkou (matches()), takže se token nedostane k JWT autentizaci. Přihlášený token NEMÁ ROLE_USER, jen role
 * podle svých scopes (ROLE_PAT_…) – access_control ho tak pustí jen na endpointy svého scope, jinde vrátí 403.
 *
 * Max. 120 požadavků na token za hodinu (cache.app, stejně jako LoginThrottler).
 */
class PersonalAccessTokenAuthenticator extends AbstractAuthenticator implements RequestMatcherInterface
{
    public const ROLE_ACTIVITY_WRITE = 'ROLE_PAT_ACTIVITY_WRITE';

    private const SCOPE_ROLES = [
        PersonalAccessToken::SCOPE_ACTIVITY_WRITE => self::ROLE_ACTIVITY_WRITE,
    ];

    private const RATE_WINDOW_SECONDS = 3600;
    private const RATE_MAX_REQUESTS = 120;
    private const TOO_MANY_REQUESTS = 'Too many requests';

    public function __construct(
        private readonly PersonalAccessTokenRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    // Výběr firewallu: jen API požadavky s osobním tokenem
    public function matches(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/') && $this->extractToken($request) !== null;
    }

    public function supports(Request $request): ?bool
    {
        return $this->extractToken($request) !== null;
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->repository->findActiveByPlainToken((string) $this->extractToken($request));
        if ($token === null) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        if (!$this->tryConsumeRequest($token)) {
            throw new CustomUserMessageAuthenticationException(self::TOO_MANY_REQUESTS);
        }

        $token->markUsed();
        $this->entityManager->flush();

        $user = $token->getUser();
        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), static fn () => $user));
        $passport->setAttribute('scopes', $token->getScopes());

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $scopes = $passport->getAttribute('scopes', []);
        $roles = array_values(array_intersect_key(self::SCOPE_ROLES, array_flip($scopes)));

        return new PostAuthenticationToken($passport->getUser(), $firewallName, $roles);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception->getMessageKey();

        return new JsonResponse(
            ['error' => $message],
            $message === self::TOO_MANY_REQUESTS ? Response::HTTP_TOO_MANY_REQUESTS : Response::HTTP_UNAUTHORIZED
        );
    }

    private function extractToken(Request $request): ?string
    {
        $header = (string) $request->headers->get('Authorization', '');
        $bearer = 'Bearer '.PersonalAccessToken::PREFIX;

        return str_starts_with($header, $bearer) ? trim(substr($header, strlen('Bearer '))) : null;
    }

    private function tryConsumeRequest(PersonalAccessToken $token): bool
    {
        $item = $this->cache->getItem('pat_requests.'.$token->getId());
        $data = $item->isHit() ? $item->get() : ['count' => 0, 'expiresAt' => time() + self::RATE_WINDOW_SECONDS];

        if ($data['count'] >= self::RATE_MAX_REQUESTS) {
            return false;
        }

        // Expirace zůstává od prvního požadavku – další požadavky okno neprodlužují
        $data['count']++;
        $item->set($data);
        $item->expiresAt((new \DateTimeImmutable())->setTimestamp($data['expiresAt']));
        $this->cache->save($item);

        return true;
    }
}
