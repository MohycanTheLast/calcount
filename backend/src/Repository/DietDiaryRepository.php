<?php

namespace App\Repository;

use App\Entity\Security\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;

class DietDiaryRepository extends EntityRepository
{
    // Kolik dní zpět vrací /api/entries (včetně dneška) – stejně jako denní karty na frontendu
    public const RECENT_DAYS = 14;

    /**
     * Součty příjmu za jednotlivé dny v rozsahu (jen dny, kde existuje aspoň jeden záznam).
     *
     * @return array<string, array{kcal: int, waterMl: int, entryCount: int}> klíč = datum Y-m-d
     */
    public function getDailyTotals(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('d.entryDate AS entryDate, SUM(d.kcal) AS totalKcal, SUM(d.waterInMl) AS totalWater, COUNT(d.id) AS entryCount')
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
            ];
        }

        return $totals;
    }

    /**
     * Posledních RECENT_DAYS dní (pro /api/entries).
     *
     * @return array<string, array{kcal: int, waterMl: int, entryCount: int}>
     */
    public function getAggregatedEntriesByDay(User $user): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->getDailyTotals($user, $today->modify(sprintf('-%d days', self::RECENT_DAYS - 1)), $today);
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
