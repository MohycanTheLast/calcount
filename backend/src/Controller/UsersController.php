<?php

namespace App\Controller;

use App\Entity\Security\User;
use App\Entity\UserSettings;
use App\Model\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class UsersController extends AbstractController
{
    // Denní cíl pitného režimu (ml) a limit kofeinu (mg), které jde nastavit
    private const WATER_GOAL_RANGE = [500, 6000];
    private const CAFFEINE_LIMIT_RANGE = [50, 1000];

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

    #[Route('/api/user/settings', name: 'api_user_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $requestData = json_decode($request->getContent(), true);
            $userSettings = $user->getNewOrExistSettings();
            $userSettings->setAvgDailyActivityKcal($requestData['avgDailyActivityKcal']);
            $userSettings->setBmrKcal($requestData['bmrKcal']);
            $userSettings->setThemeMode($requestData['themeMode']);
            $userSettings->setAiCommentMode($requestData['aiCommentMode']);
            $userSettings->setWeightKg($requestData['weightKg']);
            $userSettings->setSaveWeight($requestData['saveWeight'] === true);

            // Volitelné (frontend ho zatím neposílá) – zdroj aktivních kalorií
            if (isset($requestData['activitySource'])) {
                if (!in_array($requestData['activitySource'], UserSettings::ACTIVITY_SOURCES, true)) {
                    return new JsonResponse(['error' => 'Unknown activitySource'], Response::HTTP_BAD_REQUEST);
                }
                $userSettings->setActivitySource($requestData['activitySource']);
            }

            // Volitelné (starší verze frontendu ho neposílá) – výběr fotky z galerie
            if (isset($requestData['photoGalleryEnabled'])) {
                $userSettings->setPhotoGalleryEnabled($requestData['photoGalleryEnabled'] === true);
            }

            // Volitelné – hlídání pitného režimu, kofeinu a makroživin (pokročilé nastavení)
            try {
                if (array_key_exists('waterGoalMl', $requestData)) {
                    $userSettings->setWaterGoalMl($this->optionalInt($requestData['waterGoalMl'], self::WATER_GOAL_RANGE));
                }
                if (array_key_exists('caffeineLimitMg', $requestData)) {
                    $userSettings->setCaffeineLimitMg($this->optionalInt($requestData['caffeineLimitMg'], self::CAFFEINE_LIMIT_RANGE));
                }
            } catch (\InvalidArgumentException $e) {
                return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            if (isset($requestData['hydrationEnabled'])) {
                $userSettings->setHydrationEnabled($requestData['hydrationEnabled'] === true);
            }
            // Funkce, které typ účtu nemá, zapnout nejde (zůstanou vypnuté i při pokusu o zapnutí)
            if (isset($requestData['caffeineEnabled'])) {
                $userSettings->setCaffeineEnabled($requestData['caffeineEnabled'] === true && $user->hasFeature(AccountType::FEATURE_CAFFEINE));
            }
            if (isset($requestData['macrosEnabled'])) {
                $userSettings->setMacrosEnabled($requestData['macrosEnabled'] === true && $user->hasFeature(AccountType::FEATURE_MACROS));
            }

            $entityManager->persist($userSettings);
            $entityManager->flush();

            return new JsonResponse(status: Response::HTTP_ACCEPTED);
        }

        if ($user->getSettings() === null) {
            return $this->json(new UserSettings());
        }

        return $this->json(
            $user->getNewOrExistSettings(),
            context: ['ignored_attributes' => ['user']]
        );
    }

    /**
     * @param array{0: int, 1: int} $range povolené hodnoty včetně krajních
     */
    private function optionalInt(mixed $value, array $range): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_int($value) || $value < $range[0] || $value > $range[1]) {
            throw new \InvalidArgumentException(sprintf('Hodnota musí být celé číslo %d–%d.', $range[0], $range[1]));
        }

        return $value;
    }
}
