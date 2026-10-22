<?php

namespace App\Security;


use App\Entity\Security\User;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class AccessTokenProvider
{
    public function __construct(
        protected int $accessTokenTtlSeconds,
        protected int $refreshTokenTtlSeconds,
        protected JWTTokenManagerInterface $jwtManager,
        protected RefreshTokenGeneratorInterface $refreshTokenManager,
        protected RefreshTokenManagerInterface $refreshTokenDbManager
    ) {}

    public function get(
        User $user
    ): array {
        // Access token (JWT)
        $accessToken = $this->jwtManager->create($user);

        // Refresh token – stejná platnost jako při rotaci v /api/token/refresh (gesdinet_jwt_refresh_token.ttl)
        $refreshToken = $this->refreshTokenManager->createForUserWithTtl($user, $this->refreshTokenTtlSeconds);

        $this->refreshTokenDbManager->save($refreshToken);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'expires_in' => $this->accessTokenTtlSeconds,
        ];
    }
}
