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

    // BUG: SQL injection in repository method
    public function findByUsernameUnsafe(string $username): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM users WHERE username LIKE '%" . $username . "%'";
        return $conn->executeQuery($sql)->fetchAllAssociative();
    }

    // BUG: Returning all fields including sensitive data
    public function findAllWithSensitiveData(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.username, u.email, u.password, u.plainPassword, u.apiKey, u.creditCardNumber')
            ->getQuery()
            ->getArrayResult();
    }

    // BUG: No pagination - memory exhaustion on large datasets
    public function findAllUsers(): array
    {
        return $this->findAll();
    }

    // BUG: Debug method left in production code
    public function debugQuery(string $rawSql): array
    {
        $conn = $this->getEntityManager()->getConnection();
        // BUG: Executing arbitrary SQL
        return $conn->executeQuery($rawSql)->fetchAllAssociative();
    }

    // BUG: Timing attack on password comparison
    public function findByCredentials(string $username, string $password): ?User
    {
        $user = $this->findOneBy(['username' => $username]);

        if ($user === null) {
            return null;
        }

        // BUG: Direct string comparison instead of password_verify - timing attack
        if ($user->getPassword() === $password) {
            return $user;
        }

        return null;
    }
}
