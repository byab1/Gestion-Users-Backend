<?php

namespace App\Controller;

use App\Entity\Log;
use App\Repository\LogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/logs')]
class LogController extends AbstractController
{
    #[Route('', name: 'logs_index', methods: ['GET'])]
   #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, LogRepository $logRepository): JsonResponse
    {
        $page = (int)$request->query->get('page', 1);
        $limit = (int)$request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'ASC');
        $userFilter = $request->query->get('user');
        $actionFilter = $request->query->get('action');

        $result = $logRepository->findLogsPaginated($page, $limit, $userFilter, $actionFilter, $sort, $order);

        $data = array_map(fn($log) => [
            'id' => $log->getId(),
            'action' => $log->getAction(),
            'details' => $log->getDetails(),
            'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $log->getUser()->getId(),
                'name' => $log->getUser()->getName(),
                'email' => $log->getUser()->getEmail()
            ]
        ], $result['data']);

        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
            'logs' => $data
        ]);
    }
}
