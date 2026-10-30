<?php

namespace App\Controller;

use App\Dto\CreateDiaryEntryRequest;
use App\Dto\UpdateDiaryEntryRequest;
use App\Entity\DietDiary;
use App\Entity\Security\User;
use App\Security\Voter\DietDiaryVoter;
use App\Service\AiUsageLimiter;
use App\Service\DietDiaryService;
use App\Service\OpenAiClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;


class ApiController extends AbstractController
{
    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct(
        protected OpenAiClient $openAiClient,
        protected DietDiaryService $dietDiaryService,
        protected AiUsageLimiter $aiUsageLimiter,
        protected LoggerInterface $logger,
    )
    {
    }

    /**
     * Nový záznam z textu (odhad kalorií a vody přes AI). Tělo: { "food": "…", "date": "YYYY-MM-DD" };
     * bez data se záznam uloží k dnešku, s datem jde o zpětný zápis z detailu dne.
     */
    #[Route('/api/calculate', methods: ['POST'])]
    public function calculate(#[MapRequestPayload] CreateDiaryEntryRequest $payload): JsonResponse
    {
        if (!$this->aiUsageLimiter->tryConsume($this->getAppUser())) {
            return $this->dailyLimitExceeded();
        }

        try {
            $resultData = $this->openAiClient->estimateNutrition($payload->food);
        } catch (\Throwable $e) {
            return $this->aiFailure('calculate', $e);
        }

        $savedItem = $this->dietDiaryService->create($payload->food, $resultData, $payload->getEntryDate());

        $resultData->id = $savedItem->getId();

        return new JsonResponse($resultData);
    }

    /**
     * Ruční oprava kalorií / vody u záznamu. Tělo: { "kcal": 450, "waterInMl": 250 } (obojí volitelné).
     */
    #[Route('/api/diary-entries/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    // Starší frontend (do verze 0.2.0) – smazat po dalším vydání
    #[Route('/api/save/{id}', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(DietDiary $diaryRecord, #[MapRequestPayload] UpdateDiaryEntryRequest $payload): JsonResponse
    {
        $this->denyUnlessOwner($diaryRecord);

        $this->dietDiaryService->update($diaryRecord, $payload);

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/diary-entries/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    // Starší frontend (do verze 0.2.0) – smazat po dalším vydání
    #[Route('/api/delete/{id}', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(DietDiary $diaryRecord): JsonResponse
    {
        $this->denyUnlessOwner($diaryRecord);

        $this->dietDiaryService->delete($diaryRecord);

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/diary-day/{date}', requirements: ['date' => '\d{4}-\d{2}-\d{2}'], methods: ['GET'])]
    public function dayItems(string $date): JsonResponse
    {
        $entries = $this->dietDiaryService->getEntriesByDateStr($date);

        return new JsonResponse($entries);
    }

    #[Route('/api/upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return new JsonResponse(['error' => 'Nebyl nahrán žádný soubor.'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            return new JsonResponse(
                ['error' => sprintf('Fotka je příliš velká (max. %d MB).', self::MAX_UPLOAD_BYTES / 1024 / 1024)],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Typ podle obsahu souboru (ne podle přípony / hlavičky od klienta); jen formáty, které OpenAI přijímá.
        // Přímo přes fileinfo – UploadedFile::getMimeType() vyžaduje balíček symfony/mime, který v projektu není.
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname());
        if (!in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true)) {
            return new JsonResponse(
                ['error' => 'Nepodporovaný formát fotky (povoleno JPEG, PNG, WebP, GIF).'],
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE
            );
        }

        if (!$this->aiUsageLimiter->tryConsume($this->getAppUser())) {
            return $this->dailyLimitExceeded();
        }

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
