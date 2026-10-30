<?php

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Entity\Security\User;
use App\Entity\Security\UserIdentity;
use App\Repository\UserIdentityRepository;
use App\Repository\UserRepository;
use App\Security\AccessTokenProvider;
use App\Security\AppleIdTokenVerifier;
use App\Security\EmailVerifier;
use App\Security\GoogleIdTokenVerifier;
use App\Security\InvalidIdTokenException;
use App\Security\LoginThrottler;
use App\Security\OidcIdTokenVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserIdentityRepository $userIdentityRepository,
        protected UserPasswordHasherInterface $passwordHasher,
        protected AccessTokenProvider $accessTokenProvider,
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
        protected LoginThrottler $loginThrottler,
        protected EmailVerifier $emailVerifier,
    )
    {
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        #[MapRequestPayload] LoginRequest $payload,
        Request $request,
    ): JsonResponse {
        $email = $payload->email;
        $ip = $request->getClientIp();

        if ($this->loginThrottler->isBlocked($email, $ip)) {
            return $this->json(
                ['error' => sprintf('Příliš mnoho neúspěšných pokusů. Zkus to prosím za %d minut.', $this->loginThrottler->getWindowMinutes())],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $payload->password)) {
            $this->loginThrottler->registerFailure($email, $ip);

            return $this->json(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $this->loginThrottler->reset($email, $ip);

        // Až po ověření hesla – jinak by šlo zjistit, že účet existuje
        if ($this->emailVerifier->isEnabled() && !$user->isEmailVerified()) {
            return $this->json(
                ['error' => 'Účet ještě není aktivovaný. Klikni na odkaz v e-mailu, který jsme ti poslali.'],
                Response::HTTP_FORBIDDEN
            );
        }

        return $this->json($this->accessTokenProvider->get($user));
    }

    #[Route('/api/auth/apple', methods: ['POST'])]
    public function appleLogin(Request $request, AppleIdTokenVerifier $verifier): JsonResponse
    {
        return $this->oauthLogin($request, $verifier, UserIdentity::PROVIDER_APPLE);
    }

    #[Route('/api/auth/google', methods: ['POST'])]
    public function googleLogin(Request $request, GoogleIdTokenVerifier $verifier): JsonResponse
    {
        return $this->oauthLogin($request, $verifier, UserIdentity::PROVIDER_GOOGLE);
    }

    /**
     * Přihlášení (a případně registrace) přes ID token externího poskytovatele.
     *
     * 1. Účet už je s poskytovatelem propojený (provider + sub) → přihlásit.
     * 2. Jinak podle e-mailu – jen pokud ho poskytovatel ověřil (email_verified), jinak by šlo převzít
     *    cizí účet s neověřeným e-mailem. Existující účet se propojí, neexistující se založí.
     */
    private function oauthLogin(Request $request, OidcIdTokenVerifier $verifier, string $provider): JsonResponse
    {
        if (!$verifier->isConfigured()) {
            return $this->json(['error' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $idToken = $request->toArray()['id_token'] ?? null;
        if (!is_string($idToken) || $idToken === '') {
            return $this->json(['error' => 'Missing token'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $claims = $verifier->verify($idToken);
        } catch (InvalidIdTokenException $e) {
            $this->logger->info('OAuth login rejected', ['provider' => $provider, 'reason' => $e->getMessage()]);

            return $this->json(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        $identity = $this->userIdentityRepository->findByProvider($provider, $claims['sub']);
        if ($identity !== null) {
            return $this->json($this->accessTokenProvider->get($identity->getUser()));
        }

        $email = $claims['email'] ?? null;
        // Apple posílá email_verified jako string "true"
        $emailVerified = filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!is_string($email) || $email === '' || !$emailVerified) {
            return $this->json(['error' => 'Unverified email'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user !== null && !$user->isEmailVerified()) {
            // Heslo k účtu nastavil někdo, kdo vlastnictví e-mailu neprokázal (registrace bez ověření) – mohl to
            // být i útočník, který účet na cizí e-mail založil dopředu. Vlastník e-mailu se prokázal teď, heslo rušíme.
            $user->setPassword(null);
        }
        $user ??= (new User())->setEmail($email);
        $user->markEmailVerified();
        $user->linkIdentity($provider, $claims['sub'], $email);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json($this->accessTokenProvider->get($user));
    }
}
