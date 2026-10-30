<?php

namespace App\Controller;

use App\Dto\ManualActivityRequest;
use App\Entity\ActivityEnergy;
use App\Entity\Security\User;
use App\Repository\ActivityEnergyRepository;
use App\Service\DaySummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Aktivní kalorie za den (tabulka activity_energy, výpočet výdeje viz DaySummaryService).
 */
class ActivityController extends AbstractController
{
    // Jak daleko zpět smí synchronizace zapisovat
    private const SYNC_MAX_DAYS_BACK = 31;
    private const SYNC_MAX_DAYS_PER_REQUEST = 7;
    private const MAX_ACTIVE_KCAL = ManualActivityRequest::MAX_ACTIVE_KCAL;

    public function __construct(
        protected ActivityEnergyRepository $activityEnergyRepository,
        protected DaySummaryService $daySummaryService,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Synchronizace z Apple Zdraví – volá ji iOS Zkratka s osobním tokenem (scope activity:write),
     * později i nativní aplikace s JWT.
     *
     * Tělo: { "date": "YYYY-MM-DD", "activeKcal": 512 } nebo pole až 7 takových dní.
     * Den se přepíše (upsert), opakované odeslání nic nesčítá. Vrací souhrny uložených dní.
     *
     * Tělo se kontroluje ručně, ne přes MapRequestPayload: může to být objekt i pole a Zkratky posílají čísla
     * i jako český text („1 512,4 kcal“). Chyby mají stejný tvar { "error": … } jako zbytek API.
     */
    #[Route('/api/activity', name: 'api_activity_sync', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $items = is_array($data) && array_is_list($data) ? $data : [$data];

        if ($items === [] || count($items) > self::SYNC_MAX_DAYS_PER_REQUEST) {
            return $this->error(sprintf('Send 1 to %d days', self::SYNC_MAX_DAYS_PER_REQUEST));
        }

        $today = new \DateTimeImmutable('today');
        $values = [];

        foreach ($items as $item) {
            $date = $this->parseDate($item['date'] ?? null);
            if ($date === null) {
                return $this->error('Invalid "date", expected YYYY-MM-DD');
            }

            if (!$this->isNotInFuture($date, $today) || $date < $today->modify(sprintf('-%d days', self::SYNC_MAX_DAYS_BACK))) {
                return $this->error(sprintf('"date" must be within the last %d days', self::SYNC_MAX_DAYS_BACK));
            }

            $activeKcal = $this->parseActiveKcal($item['activeKcal'] ?? null);
            if ($activeKcal === null) {
                return $this->error(sprintf('"activeKcal" must be a number between 0 and %d', self::MAX_ACTIVE_KCAL));
            }

            $values[$date->format('Y-m-d')] = [$date, $activeKcal];
        }

        /** @var User $user */
        $user = $this->getUser();

        foreach ($values as [$date, $activeKcal]) {
            $this->upsert($user, $date, ActivityEnergy::SOURCE_APPLE_HEALTH, $activeKcal);
        }
        $this->entityManager->flush();

        return $this->json([
            'saved' => count($values),
            'days' => $this->summaries($user, array_column($values, 0)),
        ]);
    }

    /**
     * Ruční úprava aktivních kalorií dne – má přednost před synchronizací i průměrnou aktivitou.
     * Tělo: { "activeKcal": 400 }. Vrací souhrn dne.
     */
    #[Route('/api/activity/manual/{date}', name: 'api_activity_manual_set', requirements: ['date' => '\d{4}-\d{2}-\d{2}'], methods: ['PUT'])]
    public function setManual(string $date, #[MapRequestPayload] ManualActivityRequest $payload): JsonResponse
    {
        $day = $this->parseDate($date);
        if ($day === null || !$this->isNotInFuture($day, new \DateTimeImmutable('today'))) {
            return $this->error('Invalid date');
        }

        /** @var User $user */
        $user = $this->getUser();

        $this->upsert($user, $day, ActivityEnergy::SOURCE_MANUAL, (int) round((float) $payload->activeKcal));
        $this->entityManager->flush();

        return $this->json($this->summaries($user, [$day])[0]);
    }

    /**
     * Zrušení ruční úpravy – den se zase počítá podle nastavení zdroje aktivních kalorií. Vrací souhrn dne.
     */
    #[Route('/api/activity/manual/{date}', name: 'api_activity_manual_delete', requirements: ['date' => '\d{4}-\d{2}-\d{2}'], methods: ['DELETE'])]
    public function deleteManual(string $date): JsonResponse
    {
        $day = $this->parseDate($date);
        if ($day === null) {
            return $this->error('Invalid date');
        }

        /** @var User $user */
        $user = $this->getUser();

        $record = $this->activityEnergyRepository->findOneBy(['user' => $user, 'date' => $day, 'source' => ActivityEnergy::SOURCE_MANUAL]);
        if ($record !== null) {
            $this->entityManager->remove($record);
            $this->entityManager->flush();
        }

        return $this->json($this->summaries($user, [$day])[0]);
    }

    private function upsert(User $user, \DateTimeImmutable $date, string $source, int $activeKcal): void
    {
        $record = $this->activityEnergyRepository->findOneBy(['user' => $user, 'date' => $date, 'source' => $source]);

        if ($record === null) {
            $this->entityManager->persist(new ActivityEnergy($user, $date, $source, $activeKcal));
        } else {
            $record->setActiveKcal($activeKcal);
        }
    }

    /**
     * @param list<\DateTimeImmutable> $dates
     *
     * @return list<\App\Model\DaySummary> souhrny zadaných dní, vzestupně
     */
    private function summaries(User $user, array $dates): array
    {
        sort($dates);
        $wanted = array_map(static fn (\DateTimeImmutable $d) => $d->format('Y-m-d'), $dates);
        $days = $this->daySummaryService->getDays($user, $dates[0], $dates[count($dates) - 1]);

        return array_values(array_filter($days, static fn ($day) => in_array($day->date, $wanted, true)));
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }

    // Den uživatele může být o den napřed oproti serveru (časová pásma) – zítřek ještě povolíme
    private function isNotInFuture(\DateTimeImmutable $date, \DateTimeImmutable $today): bool
    {
        return $date <= $today->modify('+1 day');
    }

    /**
     * Číslo, desetinné se zaokrouhlí. Zkratky můžou poslat i text v české podobě („1 512,4 kcal“).
     */
    private function parseActiveKcal(mixed $value): ?int
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', (string) preg_replace('/[\s\x{00A0}]+|kcal$/iu', '', $value));
            $value = is_numeric($value) ? (float) $value : null;
        }

        if (!is_int($value) && !is_float($value)) {
            return null;
        }

        $kcal = (int) round($value);

        return $kcal >= 0 && $kcal <= self::MAX_ACTIVE_KCAL ? $kcal : null;
    }

    private function error(string $message): JsonResponse
    {
        return $this->json(['error' => $message], Response::HTTP_BAD_REQUEST);
    }
}
