<?php

namespace App\Controller;

use App\Entity\Security\User;
use App\Entity\UserSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

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
        ]);
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
}
