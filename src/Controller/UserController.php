<?php

namespace App\Controller;

use App\Dto\UserData;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): JsonResponse
    {

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $name = $request->query->get('name');
        $email = $request->query->get('email');
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'asc');

        $result = $userRepository->findPaginatedUsers($page, $limit, $name, $email, $sort, $order);

        return $this->json($result, 200, [], ['groups' => 'user:read']);
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerService $logger,
        ValidatorInterface $validator
        ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = new UserData();
        $data->name = $request->request->get('name');
        $data->email = $request->request->get('email');
        $data->password = $request->request->get('password');
        $data->active = $request->request->get('active') !== null ? filter_var($request->request->get('active'), FILTER_VALIDATE_BOOLEAN) : true;

        // Gestion des rôles
        $roles = $request->request->all('roles');
        if (empty($roles)) {
            $rolesRaw = $request->request->get('roles');
            $roles = $rolesRaw ? json_decode($rolesRaw, true) : [];
        }
        $data->roles = !empty($roles) ? $roles : ['ROLE_USER'];

        // Fichier
        $data->photo = $request->files->get('photo');


        // Validation obligatoire (Default + groupe create pour password)
        $errors = $validator->validate($data, null, ['Default', 'create']);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $messages], 400);
        }

        // Création de l'entité après validation
        $user = new User();
        $user->setName($data->name)
            ->setEmail($data->email)
            ->setRoles($data->roles)
            ->setActive($data->active)
            ->setPassword($passwordHasher->hashPassword($user, $data->password));

        if ($data->photo) {
            $user->setPhotoFile($data->photo);
        }

        $em->persist($user);
        $em->flush();

        $logger->log(
            $this->getUser(),
            'CREATE_USER',
            sprintf('Utilisateur %s créé avec ID %d', $user->getEmail(), $user->getId())
        );

        return $this->json(['message' => 'Utilisateur créé', 'id' => $user->getId()], 201);
    }

    #[Route('/{id}', name: 'user_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        LoggerService $logger,
        ValidatorInterface $validator
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);
        if (!$user) return $this->json(['error' => 'Utilisateur non trouvé'], 404);

        $data = new UserData();
        $data->name = $request->request->get('name') ?? $user->getName();
        $data->email = $request->request->get('email') ?? $user->getEmail();
        $data->password = $request->request->get('password'); // optionnel
        $data->active = $request->request->get('active') !== null ? filter_var($request->request->get('active'), FILTER_VALIDATE_BOOLEAN) : $user->isActive();

        // Roles
        $roles = $request->request->all('roles');
        if (empty($roles)) {
            $rolesRaw = $request->request->get('roles');
            $roles = $rolesRaw ? json_decode($rolesRaw, true) : [];
        }
        $data->roles = !empty($roles) ? $roles : $user->getRoles();

        $data->photo = $request->files->get('photo');

        // Validation (Default seulement, password optionnel)
        $errors = $validator->validate($data, null, ['Default']);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $messages], 400);
        }

        // Mise à jour de l'entité
        $user->setName($data->name)
            ->setEmail($data->email)
            ->setRoles($data->roles)
            ->setActive($data->active);

        if ($data->password) {
            $user->setPassword($passwordHasher->hashPassword($user, $data->password));
        }

        if ($data->photo) {
            $user->setPhotoFile($data->photo);
        }

        $em->flush();

        $logger->log($user, 'UPDATE_PROFILE', 'Profil mis à jour');

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, UserRepository $userRepository, LoggerService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);
        if (!$user) return $this->json(['error' => 'Utilisateur non trouvé'], 404);

        $em->remove($user);

        $logger->log(
            $this->getUser(),
            'DELETE_USER',
            sprintf('Suppression %s utilisateur : ID %d', $user->getEmail(), $user->getId())
        );

        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }
}
