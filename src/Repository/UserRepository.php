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

    public function findByUsernameSafe(string $username): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.username LIKE :username')
            ->setParameter('username', '%' . $username . '%')
            ->getQuery()
            ->getResult();
    }

    public function findAllUsersPaginated(int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // REMOVED: findAllWithSensitiveData - Use specific selections only when needed.

    // REMOVED: debugQuery - Extremely dangerous.

    public function findByCredentials(string $username, string $password): ?User
    {
        $user = $this->findOneBy(['username' => $username]);

        if ($user === null) {
            return null;
        }

        // Use password_verify to prevent timing attacks and handle hashed passwords
        if (password_verify($password, $user->getPassword())) {
            return $user;
        }

        return null;
    }
}
