<?php

namespace App\Repository;

use App\Entity\Security\User;
use Doctrine\ORM\EntityRepository;

class DietDiaryRepository extends EntityRepository
{
    public function getAggregatedEntriesByDay(User $user): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select("DATE(d.createdAt) as entryDate, SUM(d.kcal) as totalKcal, SUM(d.waterInMl) as totalWater")
            ->where('d.createdAt >= :date') // Přidána podmínka
            ->andWhere('d.user = :user')
            ->setParameter('date', (new \DateTime('-10 days'))->format('Y-m-d H:i:s')) // Dynamicky vypočítaný atribut
            ->setParameter('user', $user)
            ->groupBy("entryDate")
            ->orderBy("entryDate", "DESC");

        return $qb->getQuery()->getResult();
    }

    public function getEntriesByDateStr(string $date, User $user): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.createdAt BETWEEN :startDate AND :endDate')
            ->andWhere('d.user = :user')
            ->setParameter('startDate', (new \DateTime($date))->setTime(0, 0, 0)) // Začátek dne
            ->setParameter('endDate', (new \DateTime($date))->setTime(23, 59, 59)) // Konec dne
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'ASC'); // Volitelně seřadit

        return $qb->getQuery()->getResult();
    }


}
