<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function isEmailTaken($email, $currentUserId): bool
    {
        $existingUser = $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('u.id != :currentUserId')
            ->setParameter('email', $email)
            ->setParameter('currentUserId', $currentUserId)
            ->getQuery()
            ->getOneOrNullResult();

        return $existingUser !== null;
    }

    public function isEmailValid($email): bool
    {
        $existingUser = $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        return $existingUser !== null;
    }
}
