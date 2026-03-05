<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * @return array{items: list<AuditLog>, total: int}
     */
    public function searchPaginated(
        ?string $action,
        ?string $actor,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        int $page,
        int $limit,
        string $sort,
        string $order
    ): array {
        $qb = $this->createQueryBuilder('a');

        if ($action !== null && $action !== '') {
            $qb
                ->andWhere('LOWER(a.action) LIKE :action')
                ->setParameter('action', '%' . mb_strtolower($action) . '%');
        }

        if ($actor !== null && $actor !== '') {
            $qb
                ->andWhere('LOWER(a.actor) LIKE :actor')
                ->setParameter('actor', '%' . mb_strtolower($actor) . '%');
        }

        if ($from !== null) {
            $qb
                ->andWhere('a.createdAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to !== null) {
            $qb
                ->andWhere('a.createdAt <= :to')
                ->setParameter('to', $to);
        }

        $sortMap = [
            'id' => 'a.id',
            'createdAt' => 'a.createdAt',
            'action' => 'a.action',
            'actor' => 'a.actor',
        ];
        $sortField = $sortMap[$sort] ?? 'a.id';

        $qb->orderBy($sortField, $order);

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(a.id)')
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

