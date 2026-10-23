<?php

namespace App\Repository;

use App\Entity\Security\PersonalAccessToken;
use App\Entity\Security\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersonalAccessToken>
 */
class PersonalAccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalAccessToken::class);
    }

    public function findActiveByPlainToken(string $plainToken): ?PersonalAccessToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.tokenHash = :hash')
            ->andWhere('t.revokedAt IS NULL')
            ->setParameter('hash', PersonalAccessToken::hash($plainToken))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<PersonalAccessToken> od nejnovějšího
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.revokedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveForUser(int $id, User $user): ?PersonalAccessToken
    {
        return $this->findOneBy(['id' => $id, 'user' => $user, 'revokedAt' => null]);
    }
}
