<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Alert;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alert>
 */
class AlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    /**
     * @return Alert[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.watchedAddress', 'wa')
            ->andWhere('wa.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Alert[]
     */
    public function findActiveByWatchedAddress(int $watchedAddressId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.watchedAddress = :id')
            ->andWhere('a.isActive = true')
            ->setParameter('id', $watchedAddressId)
            ->getQuery()
            ->getResult();
    }
}
