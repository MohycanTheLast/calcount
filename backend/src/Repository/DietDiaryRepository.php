<?php

namespace App\Repository;

use App\Entity\Security\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;

class DietDiaryRepository extends EntityRepository
{
    /**
     * Součty příjmu za jednotlivé dny v rozsahu (jen dny, kde existuje aspoň jeden záznam).
     *
     * Makroživiny a kofein: SUM přeskočí záznamy bez odhadu (null), COUNT(d.proteinG) je počet záznamů s odhadem.
     *
     * @return array<string, array{kcal: int, waterMl: int, entryCount: int, proteinG: int, carbsG: int, sugarG: int, fatG: int, caffeineMg: int, nutrientsEntryCount: int}> klíč = datum Y-m-d
     */
    public function getDailyTotals(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('d.entryDate AS entryDate, SUM(d.kcal) AS totalKcal, SUM(d.waterInMl) AS totalWater, COUNT(d.id) AS entryCount')
            ->addSelect('SUM(d.proteinG) AS totalProtein, SUM(d.carbsG) AS totalCarbs, SUM(d.sugarG) AS totalSugar')
            ->addSelect('SUM(d.fatG) AS totalFat, SUM(d.caffeineMg) AS totalCaffeine, COUNT(d.proteinG) AS nutrientsEntryCount')
            ->where('d.user = :user')
            ->andWhere('d.entryDate BETWEEN :from AND :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->setParameter('to', $to, Types::DATE_IMMUTABLE)
            ->groupBy('d.entryDate')
            ->orderBy('d.entryDate', 'ASC')
            ->getQuery()
            ->getResult();

        $totals = [];
        foreach ($rows as $row) {
            $date = $row['entryDate'] instanceof \DateTimeInterface
                ? $row['entryDate']->format('Y-m-d')
                : (string) $row['entryDate'];

            $totals[$date] = [
                'kcal' => (int) $row['totalKcal'],
                'waterMl' => (int) $row['totalWater'],
                'entryCount' => (int) $row['entryCount'],
                'proteinG' => (int) $row['totalProtein'],
                'carbsG' => (int) $row['totalCarbs'],
                'sugarG' => (int) $row['totalSugar'],
                'fatG' => (int) $row['totalFat'],
                'caffeineMg' => (int) $row['totalCaffeine'],
                'nutrientsEntryCount' => (int) $row['nutrientsEntryCount'],
            ];
        }

        return $totals;
    }

    public function getEntriesByDateStr(string $date, User $user): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.entryDate = :date')
            ->andWhere('d.user = :user')
            ->setParameter('date', new \DateTimeImmutable($date), Types::DATE_IMMUTABLE)
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
