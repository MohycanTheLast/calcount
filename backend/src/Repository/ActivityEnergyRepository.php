<?php

namespace App\Repository;

use App\Entity\ActivityEnergy;
use App\Entity\Security\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityEnergy>
 */
class ActivityEnergyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityEnergy::class);
    }

    /**
     * @return array<string, list<ActivityEnergy>> klíč = datum Y-m-d
     */
    public function findByUserAndRangeGroupedByDate(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<ActivityEnergy> $rows */
        $rows = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.date BETWEEN :from AND :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->setParameter('to', $to, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getResult();

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row->getDate()->format('Y-m-d')][] = $row;
        }

        return $byDate;
    }
}
