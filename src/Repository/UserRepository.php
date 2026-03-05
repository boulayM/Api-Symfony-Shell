<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    /**
     * @return array{items: list<User>, total: int}
     */
    public function searchPaginated(
        ?string $q,
        ?string $role,
        ?bool $isVerified,
        int $page,
        int $limit,
        string $sort,
        string $order
    ): array {
        $qb = $this->createQueryBuilder('u');

        if ($q !== null && $q !== '') {
            $like = '%' . mb_strtolower($q) . '%';
            $qb
                ->andWhere('LOWER(u.email) LIKE :q OR LOWER(COALESCE(u.firstName, \'\')) LIKE :q OR LOWER(COALESCE(u.lastName, \'\')) LIKE :q')
                ->setParameter('q', $like);
        }

        if ($role !== null && $role !== '') {
            $qb
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        if ($isVerified !== null) {
            $qb
                ->andWhere('u.isVerified = :isVerified')
                ->setParameter('isVerified', $isVerified);
        }

        $sortMap = [
            'id' => 'u.id',
            'email' => 'u.email',
            'createdAt' => 'u.createdAt',
            'firstName' => 'u.firstName',
            'lastName' => 'u.lastName',
            'isVerified' => 'u.isVerified',
        ];
        $sortField = $sortMap[$sort] ?? 'u.id';

        $qb->orderBy($sortField, $order);

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
