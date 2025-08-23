<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Log>
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    //    /**
    //     * @return Log[] Returns an array of Log objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Log
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @param int $page
     * @param int $limit
     * @param string|null $userFilter
     * @param string|null $actionFilter
     * @param string $sort
     * @param string $order
     * @return array ['data' => Log[], 'total' => int]
     */
    public function findLogsPaginated(
        int $page = 1,
        int $limit = 10,
        ?string $userFilter = null,
        ?string $actionFilter = null,
        string $sort = 'id',
        string $order = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u');

        // Filtrage
        if ($userFilter) {
            $qb->andWhere('u.name LIKE :user OR u.email LIKE :user')
               ->setParameter('user', '%'.$userFilter.'%');
        }
        if ($actionFilter) {
            $qb->andWhere('l.action LIKE :action')
               ->setParameter('action', '%'.$actionFilter.'%');
        }

        // Tri
        $allowedSortFields = ['id', 'action', 'createdAt', 'details', 'user'];
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'id';
        }
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        if ($sort === 'user') {
            $qb->orderBy('u.name', $order);
        } else {
            $qb->orderBy('l.'.$sort, $order);
        }

        // Pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $logs = $qb->getQuery()->getResult();

        // Total
        $countQb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->leftJoin('l.user', 'u');

        if ($userFilter) {
            $countQb->andWhere('u.name LIKE :user OR u.email LIKE :user')
                    ->setParameter('user', '%'.$userFilter.'%');
        }
        if ($actionFilter) {
            $countQb->andWhere('l.action LIKE :action')
                    ->setParameter('action', '%'.$actionFilter.'%');
        }

        $total = (int)$countQb->getQuery()->getSingleScalarResult();

        return [
            'data' => $logs,
            'total' => $total,
        ];
    }
}
