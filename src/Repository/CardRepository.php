<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /**
     * @return array<string, list<Card>>
     */
    public function findGroupedByColumn(): array
    {
        $cards = $this->createQueryBuilder('c')
            ->orderBy('c.columnKey', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [
            Card::COLUMN_WIP => [],
            Card::COLUMN_PRIO_1 => [],
            Card::COLUMN_PRIO_2 => [],
        ];

        foreach ($cards as $card) {
            $grouped[$card->getColumnKey()][] = $card;
        }

        return $grouped;
    }

    /**
     * @return list<Card>
     */
    public function findByColumnOrdered(string $columnKey): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.columnKey = :columnKey')
            ->setParameter('columnKey', $columnKey)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMaxPosition(string $columnKey): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COALESCE(MAX(c.position), 0)')
            ->andWhere('c.columnKey = :columnKey')
            ->setParameter('columnKey', $columnKey)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
