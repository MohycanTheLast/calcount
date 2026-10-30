<?php

namespace App\Controller;

use App\Dto\RegisterRequest;
use App\Entity\Security\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\RegistrationThrottler;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    // Verze podmínek užívání = datum jejich účinnosti. Měnit spolu s frontend/app/pages/terms.vue.
    public const TERMS_VERSION = '2026-10-26';

    public function __construct(
        protected UserRepository $userRepository,
        protected UserPasswordHasherInterface $passwordHasher,
        protected EntityManagerInterface $entityManager,
        protected EmailVerifier $emailVerifier,
        protected RegistrationThrottler $registrationThrottler,
    ) {
    }

    /**
     * Registrace e-mailem a heslem.
     *
     * Odpověď 201 se stavem:
     * - `registered` – účet je hotový, lze se přihlásit (ověření e-mailem vypnuté),
     * - `verification_pending` – účet čeká na potvrzení odkazem z e-mailu.
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $payload, Request $request): JsonResponse
    {
        $email = $payload->getNormalizedEmail();

        $ip = $request->getClientIp();
        if ($this->registrationThrottler->isBlocked($ip)) {
            return $this->json(
                ['error' => sprintf('Příliš mnoho registrací. Zkus to prosím za %d minut.', $this->registrationThrottler->getWindowMinutes())],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }
        $this->registrationThrottler->registerAttempt($ip);

        if ($this->userRepository->findOneBy(['email' => $email]) !== null) {
            return $this->existingAccountResponse();
        }

        $user = (new User())
            ->setEmail($email)
            ->acceptTerms(self::TERMS_VERSION);
        $user->setPassword($this->passwordHasher->hashPassword($user, $payload->password));

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Souběžná registrace stejného e-mailu
            return $this->existingAccountResponse();
        }

        if ($this->emailVerifier->isEnabled()) {
            $this->emailVerifier->sendVerificationEmail($user);

            return $this->json(['status' => 'verification_pending'], Response::HTTP_CREATED);
        }

        return $this->json(['status' => 'registered'], Response::HTTP_CREATED);
    }

    /**
     * Potvrzení e-mailu odkazem z registračního e-mailu (frontend /verify-email posílá parametry odkazu).
     */
    #[Route('/api/register/verify', name: 'api_register_verify', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $uid = filter_var($data['uid'] ?? null, FILTER_VALIDATE_INT);
        $expires = filter_var($data['expires'] ?? null, FILTER_VALIDATE_INT);
        $signature = $data['signature'] ?? null;

        $user = $uid !== false ? $this->userRepository->find($uid) : null;

        if ($user === null || $expires === false || !is_string($signature)
            || !$this->emailVerifier->isValid($user, $expires, $signature)) {
            return $this->json(['error' => 'Odkaz je neplatný nebo už vypršel.'], Response::HTTP_BAD_REQUEST);
        }

        $user->markEmailVerified();
        $this->entityManager->flush();

        return $this->json(['status' => 'verified']);
    }

    private function existingAccountResponse(): JsonResponse
    {
        // S ověřením e-mailem odpovídáme stejně jako při úspěchu, aby nešlo zjišťovat, kdo má účet.
        // Bez ověření by to nemělo smysl (účet by se hned vytvořil) – uživateli řekneme, ať se přihlásí.
        if ($this->emailVerifier->isEnabled()) {
            return $this->json(['status' => 'verification_pending'], Response::HTTP_CREATED);
        }

        return $this->json(['error' => 'Účet s tímto e-mailem už existuje. Zkus se přihlásit.'], Response::HTTP_CONFLICT);
    }
}
