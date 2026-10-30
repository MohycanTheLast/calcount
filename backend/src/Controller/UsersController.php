<?php

namespace App\Controller;

use App\Dto\UserSettingsRequest;
use App\Entity\Security\User;
use App\Entity\UserSettings;
use App\Model\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class UsersController extends AbstractController
{
    #[Route('/api/user/me', name: 'api_user_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        return $this->json([
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'user_identifier' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'onboardingCompleted' => $user->isOnboardingCompleted(),
            // Typ účtu a funkce, které k němu patří (App\Model\AccountType)
            'accountType' => $user->getAccountType(),
            'features' => AccountType::features($user->getAccountType()),
        ]);
    }

    /**
     * Uživatel dokončil nebo přeskočil úvodního průvodce – při dalším přihlášení se už nezobrazí.
     */
    #[Route('/api/user/onboarding', name: 'api_user_onboarding', methods: ['POST'])]
    public function completeOnboarding(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->completeOnboarding();
        $entityManager->flush();

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/user/settings', name: 'api_user_settings', methods: ['GET'])]
    public function settings(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getSettings() === null) {
            return $this->json(new UserSettings());
        }

        return $this->json(
            $user->getNewOrExistSettings(),
            context: ['ignored_attributes' => ['user']]
        );
    }

    #[Route('/api/user/settings', name: 'api_user_settings_save', methods: ['POST'])]
    public function saveSettings(#[MapRequestPayload] UserSettingsRequest $payload, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $userSettings = $user->getNewOrExistSettings();
        $userSettings->setAvgDailyActivityKcal($this->roundOrNull($payload->avgDailyActivityKcal));
        $userSettings->setBmrKcal($this->roundOrNull($payload->bmrKcal));
        $userSettings->setThemeMode($payload->themeMode);
        $userSettings->setAiCommentMode($payload->aiCommentMode);
        $userSettings->setWeightKg($this->roundOrNull($payload->weightKg));
        $userSettings->setSaveWeight($payload->saveWeight);

        // Volitelné (starší verze frontendu je neposílá) – null = beze změny
        if ($payload->activitySource !== null) {
            $userSettings->setActivitySource($payload->activitySource);
        }
        if ($payload->photoGalleryEnabled !== null) {
            $userSettings->setPhotoGalleryEnabled($payload->photoGalleryEnabled);
        }
        if ($payload->hydrationEnabled !== null) {
            $userSettings->setHydrationEnabled($payload->hydrationEnabled);
        }
        // Funkce, které typ účtu nemá, zapnout nejde (zůstanou vypnuté i při pokusu o zapnutí)
        if ($payload->caffeineEnabled !== null) {
            $userSettings->setCaffeineEnabled($payload->caffeineEnabled && $user->hasFeature(AccountType::FEATURE_CAFFEINE));
        }
        if ($payload->macrosEnabled !== null) {
            $userSettings->setMacrosEnabled($payload->macrosEnabled && $user->hasFeature(AccountType::FEATURE_MACROS));
        }

        // Cíl vody a limit kofeinu: null = výchozí hodnota (utils/tracking.ts ve frontendu)
        $userSettings->setWaterGoalMl($payload->waterGoalMl);
        $userSettings->setCaffeineLimitMg($payload->caffeineLimitMg);

        $entityManager->persist($userSettings);
        $entityManager->flush();

        return new JsonResponse(status: Response::HTTP_ACCEPTED);
    }

    private function roundOrNull(?float $value): ?int
    {
        return $value === null ? null : (int) round($value);
    }
}
