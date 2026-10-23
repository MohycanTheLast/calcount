<?php

namespace App\Controller;

use App\Entity\DietDiary;
use App\Entity\Security\User;
use App\Security\Voter\DietDiaryVoter;
use App\Service\AiUsageLimiter;
use App\Service\DietDiaryService;
use App\Service\OpenAiClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class ApiController extends AbstractController
{
    // Delší text by zbytečně zvyšoval cenu volání AI (a do DB se stejně ukládá jen 255 znaků)
    private const MAX_FOOD_TEXT_LENGTH = 500;

    public function __construct(
        protected OpenAiClient $openAiClient,
        protected DietDiaryService $dietDiaryService,
        protected AiUsageLimiter $aiUsageLimiter,
        protected LoggerInterface $logger,
    )
    {
    }

    #[Route('/api/entries', methods: ['GET'])]
    public function entries(): JsonResponse
    {
        return new JsonResponse($this->dietDiaryService->getAllEntries());
    }

    #[Route('/api/calculate', methods: ['POST'])]
    public function calculate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = is_array($data) ? ($data['food'] ?? null) : null;

        if (!is_string($text) || trim($text) === '') {
            return new JsonResponse(["error" => "No data provided"], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($text) > self::MAX_FOOD_TEXT_LENGTH) {
            return new JsonResponse(
                ["error" => sprintf('Text je příliš dlouhý (max. %d znaků).', self::MAX_FOOD_TEXT_LENGTH)],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$this->aiUsageLimiter->tryConsume($this->getAppUser())) {
            return $this->dailyLimitExceeded();
        }

        try {
            $resultData = $this->openAiClient->estimateNutrition($text);
        } catch (\Throwable $e) {
            return $this->aiFailure('calculate', $e);
        }

        $savedItem = $this->dietDiaryService->create($text, $resultData);

        $resultData->id = $savedItem->getId();

        return new JsonResponse($resultData);
    }

    #[Route('/api/save/{id}', methods: ['POST'])]
    public function save(DietDiary $diaryRecord, Request $request): JsonResponse
    {
        $this->denyUnlessOwner($diaryRecord);

        $data = json_decode($request->getContent(), true);
        $this->dietDiaryService->update($diaryRecord, is_array($data) ? $data : []);

        return new JsonResponse(status: Response::HTTP_ACCEPTED);
    }

    #[Route('/api/delete/{id}', methods: ['POST'])]
    public function delete(DietDiary $diaryRecord): JsonResponse
    {
        $this->denyUnlessOwner($diaryRecord);

        $this->dietDiaryService->delete($diaryRecord);

        return new JsonResponse(status: Response::HTTP_ACCEPTED);
    }

    #[Route('/api/diary-day/{date}', methods: ['GET'])]
    public function dayItems(string $date): JsonResponse
    {
        $entries = $this->dietDiaryService->getEntriesByDateStr($date);

        return new JsonResponse($entries);
    }

    #[Route('/api/upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'Nebyl nahrán žádný soubor.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->aiUsageLimiter->tryConsume($this->getAppUser())) {
            return $this->dailyLimitExceeded();
        }

        $mimeType = $file->getMimeType() ?? 'image/jpeg';
        $imageData = base64_encode(file_get_contents($file->getPathname()));

        try {
            $food = $this->openAiClient->analyzeImage($imageData, $mimeType);
        } catch (\Throwable $e) {
            return $this->aiFailure('upload', $e);
        }

        return new JsonResponse(['food' => $food]);
    }

    private function getAppUser(): User
    {
        /** @var User $user firewall /api vyžaduje přihlášení */
        $user = $this->getUser();

        return $user;
    }

    private function denyUnlessOwner(DietDiary $record): void
    {
        // 404 místo 403 – cizí záznamy se navenek tváří jako neexistující
        if (!$this->isGranted(DietDiaryVoter::EDIT, $record)) {
            throw $this->createNotFoundException();
        }
    }

    private function dailyLimitExceeded(): JsonResponse
    {
        return new JsonResponse(
            ['error' => sprintf('Dnešní limit %d dotazů na AI je vyčerpaný. Zkus to prosím zítra.', $this->aiUsageLimiter->getDailyLimit())],
            Response::HTTP_TOO_MANY_REQUESTS
        );
    }

    // Detail chyby jen do logu – klientovi neposíláme interní zprávy (např. z OpenAI API)
    private function aiFailure(string $action, \Throwable $e): JsonResponse
    {
        $this->logger->error('OpenAI request failed', ['action' => $action, 'exception' => $e]);

        return new JsonResponse(
            ['error' => 'Výpočet se nepodařil, zkus to prosím znovu.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
