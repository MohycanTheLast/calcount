<?php

namespace App\Controller;

use App\Entity\Security\User;
use App\Repository\UserRepository;
use App\Security\AccessTokenProvider;
use App\Security\AppleIdTokenVerifier;
use App\Security\GoogleIdTokenVerifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserPasswordHasherInterface $passwordHasher,
        protected AccessTokenProvider $accessTokenProvider
    )
    {
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(
                ['error' => 'Missing credentials'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $this->json($this->accessTokenProvider->get($user));
    }

    #[Route('/api/auth/apple', methods: ['POST'])]
    public function appleLogin(
        Request $request,
        AppleIdTokenVerifier $verifier
    ): JsonResponse {
        $idToken = $request->toArray()['id_token'] ?? null;
        if (!$idToken) {
            return $this->json(['error' => 'Missing token'], 400);
        }

        $claims = $verifier->verify($idToken);

        $appleId = $claims['sub'];
        $email = $claims['email'] ?? null;

        $user = $this->userRepository->findOneBy(['appleId' => $appleId]);

        if (!$user) {
            // fallback přes email (pokud už existuje účet)
            if ($email) {
                $user = $this->userRepository->findOneBy(['email' => $email]);
            }

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
            }

            $user->setAppleId($appleId);
            // TODO save
//            $em->persist($user);
//            $em->flush();
        }

        return $this->json($this->accessTokenProvider->get($user));
    }

    #[Route('/api/auth/google', methods: ['POST'])]
    public function googleLogin(
        Request $request,
        GoogleIdTokenVerifier $verifier
    ): JsonResponse {
        $claims = $verifier->verify(
            $request->toArray()['id_token']
        );

        $googleId = $claims['sub'];
        $email = $claims['email'];

        $user = $this->userRepository->findOneBy(['googleId' => $googleId])
            ?? $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
        }

        $user->setGoogleId($googleId);
        // TODO save
//        $em->persist($user);
//        $em->flush();

        return $this->json($this->accessTokenProvider->get($user));
    }
}
