<?php

namespace App\Controller;

use App\Entity\DietDiary;
use App\Service\DietDiaryService;
use App\Service\OpenAiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class ApiController extends AbstractController
{
    public function __construct(
        protected OpenAiClient $openAiClient,
        protected DietDiaryService $dietDiaryService
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

        if (!array_key_exists("food", $data)) {
            return new JsonResponse(["error" => "No data provided"], Response::HTTP_BAD_REQUEST);
        }

        $text = $data["food"];

        try {
            $resultData = $this->openAiClient->estimateNutrition($text);
        } catch (\Throwable $e) {
            return new JsonResponse([
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $savedItem = $this->dietDiaryService->create($text, $resultData);

        $resultData->id = $savedItem->getId();

        return new JsonResponse($resultData);
    }

    #[Route('/api/save/{id}', methods: ['POST'])]
    public function save(DietDiary $diaryRecord, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->dietDiaryService->update($diaryRecord, $data);

        return new JsonResponse(status: Response::HTTP_ACCEPTED);
    }

    #[Route('/api/delete/{id}', methods: ['POST'])]
    public function delete(DietDiary $diaryRecord): JsonResponse
    {
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

        $mimeType = $file->getMimeType() ?? 'image/jpeg';
        $imageData = base64_encode(file_get_contents($file->getPathname()));

        try {
            $food = $this->openAiClient->analyzeImage($imageData, $mimeType);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['food' => $food]);
    }
}
