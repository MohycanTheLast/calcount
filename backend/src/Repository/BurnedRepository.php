<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class BurnedRepository extends EntityRepository
{
    /**
     * Získá agregované spálené kalorie seskupené podle data
     *
     * @return array
     */
    public function getAggregatedBurnedCaloriesByDay(): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select("DATE(b.createdAt) as entryDate, SUM(b.kcal) as totalBurnedKcal")
            ->groupBy("entryDate")
            ->orderBy("entryDate", "ASC");

        return $qb->getQuery()->getResult();
    }
}
