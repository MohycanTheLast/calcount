<?php

namespace App\Controller;

use App\Dto\CreateTokenRequest;
use App\Entity\Security\PersonalAccessToken;
use App\Entity\Security\User;
use App\Entity\UserSettings;
use App\Repository\PersonalAccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Správa osobních API tokenů z aplikace (jen s JWT – osobní token sám tokeny spravovat nesmí).
 */
class PersonalAccessTokensController extends AbstractController
{
    private const MAX_ACTIVE_TOKENS = 5;
    private const DEFAULT_NAME = 'iPhone – Zkratky';

    public function __construct(
        protected PersonalAccessTokenRepository $tokenRepository,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/tokens', name: 'api_tokens_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(array_map($this->serialize(...), $this->tokenRepository->findActiveByUser($user)));
    }

    /**
     * Nový token pro zápis aktivních kalorií. Plný token je v odpovědi jen teď, v DB je jen jeho hash.
     *
     * Při prvním tokenu se zdroj aktivních kalorií přepne z průměrné aktivity na „synchronizované, jinak průměr“,
     * aby se odeslaná data hned projevila. Odpověď proto obsahuje i aktuální activitySource.
     */
    #[Route('/api/tokens', name: 'api_tokens_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateTokenRequest $payload = new CreateTokenRequest()): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeTokens = $this->tokenRepository->findActiveByUser($user);
        if (count($activeTokens) >= self::MAX_ACTIVE_TOKENS) {
            return $this->json(
                ['error' => sprintf('Můžeš mít nejvýš %d tokenů. Nejdřív některý odvolej.', self::MAX_ACTIVE_TOKENS)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $name = trim($payload->name);
        if ($name === '') {
            $name = self::DEFAULT_NAME;
        }

        [$token, $plainToken] = PersonalAccessToken::generate($user, $name, [PersonalAccessToken::SCOPE_ACTIVITY_WRITE]);
        $this->entityManager->persist($token);

        $settings = $user->getNewOrExistSettings();
        if ($activeTokens === [] && $settings->getActivitySource() === UserSettings::ACTIVITY_SOURCE_STATIC) {
            $settings->setActivitySource(UserSettings::ACTIVITY_SOURCE_SYNCED_OR_STATIC);
            $this->entityManager->persist($settings);
        }

        $this->entityManager->flush();

        return $this->json(
            [...$this->serialize($token), 'token' => $plainToken, 'activitySource' => $settings->getActivitySource()],
            Response::HTTP_CREATED
        );
    }

    #[Route('/api/tokens/{id}', name: 'api_tokens_revoke', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function revoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Cizí nebo neexistující token – 404 (stejně jako u záznamů deníku)
        $token = $this->tokenRepository->findActiveForUser($id, $user);
        if ($token === null) {
            return $this->json(['error' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $token->revoke();
        $this->entityManager->flush();

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }

    private function serialize(PersonalAccessToken $token): array
    {
        return [
            'id' => $token->getId(),
            'name' => $token->getName(),
            'prefix' => $token->getDisplayPrefix(),
            'createdAt' => $token->getCreatedAt()->format(\DATE_ATOM),
            'lastUsedAt' => $token->getLastUsedAt()?->format(\DATE_ATOM),
        ];
    }
}
