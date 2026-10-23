<?php

namespace App\Controller;

use App\Entity\Security\User;
use App\Service\DaySummaryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DaysController extends AbstractController
{
    // Výchozí rozsah bez parametrů: posledních 14 dní včetně dneška (denní karty v Deníku)
    private const DEFAULT_DAYS = 14;

    public function __construct(
        protected DaySummaryService $daySummaryService,
    ) {
    }

    /**
     * GET /api/days?from=YYYY-MM-DD&to=YYYY-MM-DD – souhrn každého dne v rozsahu (viz App\Model\DaySummary).
     */
    #[Route('/api/days', methods: ['GET'])]
    public function days(Request $request): JsonResponse
    {
        try {
            $to = $this->parseDate($request->query->get('to'), new \DateTimeImmutable('today'));
            $from = $this->parseDate($request->query->get('from'), $to->modify(sprintf('-%d days', self::DEFAULT_DAYS - 1)));
        } catch (\InvalidArgumentException) {
            return $this->json(['error' => 'Invalid date, expected YYYY-MM-DD'], Response::HTTP_BAD_REQUEST);
        }

        if ($from > $to) {
            return $this->json(['error' => '"from" must not be after "to"'], Response::HTTP_BAD_REQUEST);
        }

        if ($from->diff($to)->days + 1 > DaySummaryService::MAX_RANGE_DAYS) {
            return $this->json(
                ['error' => sprintf('Range is limited to %d days', DaySummaryService::MAX_RANGE_DAYS)],
                Response::HTTP_BAD_REQUEST
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->json($this->daySummaryService->getDays($user, $from, $to));
    }

    private function parseDate(?string $value, \DateTimeImmutable $default): \DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException($value);
        }

        return $date;
    }
}
