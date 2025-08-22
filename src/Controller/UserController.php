<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Log;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, (int) $request->query->get('limit', 10));
        $name = $request->query->get('name');
        $email = $request->query->get('email');

        $qb = $userRepository->createQueryBuilder('u');

        if ($name) {
            $qb->andWhere('u.name LIKE :name')->setParameter('name', "%$name%");
        }
        if ($email) {
            $qb->andWhere('u.email LIKE :email')->setParameter('email', "%$email%");
        }

        $qb->orderBy('u.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $users = $qb->getQuery()->getResult();
        $total = (clone $qb)->select('COUNT(u.id)')->setFirstResult(0)->setMaxResults(null)->getQuery()->getSingleScalarResult();

        $data = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName(),
            'email' => $u->getEmail(),
            'roles' => $u->getRoles(),
            'active' => $u->isActive(),
            'createdAt' => $u->getCreatedAt()->format('Y-m-d H:i:s'),
            'photo' => $u->getPhoto(),
        ], $users);

        return $this->json([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setActive($data['active'] ?? true);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $em->persist($user);

        // Log
        $log = new Log();
        $log->setUser($this->getUser());
        $log->setAction("Création utilisateur: " . $user->getEmail());
        $em->persist($log);

        $em->flush();

        return $this->json(['message' => 'Utilisateur créé', 'id' => $user->getId()], 201);
    }

    #[Route('/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $userRepository->find($id);
        if (!$user) return $this->json(['error' => 'Utilisateur non trouvé'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) $user->setName($data['name']);
        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['roles'])) $user->setRoles($data['roles']);
        if (isset($data['active'])) $user->setActive($data['active']);
        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $em->persist($user);

        // Log
        $log = new Log();
        $log->setUser($this->getUser());
        $log->setAction("Mise à jour utilisateur: " . $user->getEmail());
        $em->persist($log);

        $em->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) return $this->json(['error' => 'Utilisateur non trouvé'], 404);

        $em->remove($user);

        // Log
        $log = new Log();
        $log->setUser($this->getUser());
        $log->setAction("Suppression utilisateur: " . $user->getEmail());
        $em->persist($log);

        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }
}
