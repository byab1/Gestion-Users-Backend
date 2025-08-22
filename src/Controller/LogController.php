<?php

namespace App\Controller;

use App\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/logs')]
class LogController extends AbstractController
{
    #[Route('', name: 'logs_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); 

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, (int) $request->query->get('limit', 10));
        $search = $request->query->get('search');

        $qb = $em->getRepository(Log::class)->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('l.action LIKE :search OR l.details LIKE :search OR u.email LIKE :search')
               ->setParameter('search', "%$search%");
        }

        $total = (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();

        $logs = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $data = array_map(function (Log $log) {
            return [
                'id' => $log->getId(),
                'action' => $log->getAction(),
                'details' => $log->getDetails(),
                'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
                'user' => $log->getUser() ? [
                    'id' => $log->getUser()->getId(),
                    'email' => $log->getUser()->getEmail(),
                ] : null
            ];
        }, $logs);

        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'logs' => $data
        ]);
    }
}
