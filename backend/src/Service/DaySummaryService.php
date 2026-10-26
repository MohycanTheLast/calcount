<?php

namespace App\Service;

use App\Entity\ActivityEnergy;
use App\Entity\DietDiary;
use App\Entity\Security\User;
use App\Entity\UserSettings;
use App\Model\DaySummary;
use App\Repository\ActivityEnergyRepository;
use App\Repository\DietDiaryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Souhrny dní: příjem, výdej a bilance – na jednom místě pro všechny obrazovky.
 *
 * Výdej = BMR + aktivní kalorie dne. Aktivní kalorie se vybírají takto:
 *  1. ruční zápis za den (zdroj „manual“) má vždy přednost,
 *  2. jinak podle nastavení activitySource (static / synced / synced_or_static).
 * Víc synchronizovaných zdrojů za den se nesčítá (aktivity ze Stravy bývají i v Apple Health) – bere se maximum.
 * BMR i průměrná aktivita jsou aktuální hodnoty z nastavení (historie nastavení se zatím neukládá).
 */
class DaySummaryService
{
    public const MAX_RANGE_DAYS = 366;

    // Den bez záznamu
    private const EMPTY_DAY = [
        'kcal' => 0, 'waterMl' => 0, 'entryCount' => 0,
        'proteinG' => 0, 'carbsG' => 0, 'sugarG' => 0, 'fatG' => 0, 'caffeineMg' => 0, 'nutrientsEntryCount' => 0,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityEnergyRepository $activityEnergyRepository,
    ) {
    }

    /**
     * Každý den v rozsahu (včetně dnů bez záznamu), vzestupně.
     *
     * @return list<DaySummary>
     */
    public function getDays(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $from = $from->setTime(0, 0);
        $to = $to->setTime(0, 0);

        /** @var DietDiaryRepository $dietDiaryRepository */
        $dietDiaryRepository = $this->entityManager->getRepository(DietDiary::class);
        $intakeByDate = $dietDiaryRepository->getDailyTotals($user, $from, $to);
        $activityByDate = $this->activityEnergyRepository->findByUserAndRangeGroupedByDate($user, $from, $to);

        $settings = $user->getSettings();
        $bmr = $settings?->getBmrKcal() ?: null;
        $staticActivity = $settings?->getAvgDailyActivityKcal();
        $activitySource = $settings?->getActivitySource() ?? UserSettings::ACTIVITY_SOURCE_STATIC;

        $days = [];
        for ($day = $from; $day <= $to; $day = $day->modify('+1 day')) {
            $date = $day->format('Y-m-d');
            $intake = $intakeByDate[$date] ?? self::EMPTY_DAY;

            [$activeKcal, $activeKcalSource] = $this->resolveActiveKcal($activityByDate[$date] ?? [], $activitySource, $staticActivity);

            $expenditure = $bmr !== null ? $bmr + ($activeKcal ?? 0) : null;

            $days[] = new DaySummary(
                date: $date,
                kcal: $intake['kcal'],
                waterMl: $intake['waterMl'],
                entryCount: $intake['entryCount'],
                bmrKcal: $bmr,
                activeKcal: $activeKcal,
                activeSource: $activeKcalSource,
                expenditureKcal: $expenditure,
                balanceKcal: $expenditure !== null ? $intake['kcal'] - $expenditure : null,
                proteinG: $intake['proteinG'],
                carbsG: $intake['carbsG'],
                sugarG: $intake['sugarG'],
                fatG: $intake['fatG'],
                caffeineMg: $intake['caffeineMg'],
                nutrientsEntryCount: $intake['nutrientsEntryCount'],
            );
        }

        return $days;
    }

    /**
     * @param list<ActivityEnergy> $records záznamy aktivity za jeden den
     *
     * @return array{0: ?int, 1: string} [aktivní kcal, použitý zdroj]
     */
    private function resolveActiveKcal(array $records, string $activitySource, ?int $staticActivity): array
    {
        $synced = null;
        foreach ($records as $record) {
            if ($record->isManual()) {
                return [$record->getActiveKcal(), ActivityEnergy::SOURCE_MANUAL];
            }

            if ($synced === null || $record->getActiveKcal() > $synced->getActiveKcal()) {
                $synced = $record;
            }
        }

        $static = $staticActivity !== null ? [$staticActivity, UserSettings::ACTIVITY_SOURCE_STATIC] : [null, 'none'];

        return match ($activitySource) {
            UserSettings::ACTIVITY_SOURCE_SYNCED => $synced ? [$synced->getActiveKcal(), $synced->getSource()] : [null, 'none'],
            UserSettings::ACTIVITY_SOURCE_SYNCED_OR_STATIC => $synced ? [$synced->getActiveKcal(), $synced->getSource()] : $static,
            default => $static,
        };
    }
}
