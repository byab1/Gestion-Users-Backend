<?php

namespace App\Controller;

use App\Dto\UserData;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/me')]
class MeController extends AbstractController
{
    #[Route('', name: 'me_get', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id'        => $user->getId(),
            'name'      => $user->getName(),
            'email'     => $user->getEmail(),
            'roles'     => $user->getRoles(),
            'active'    => $user->isActive(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'photo'     => $user->getPhoto(),
        ]);
    }

    #[Route('', name: 'me_update', methods: ['POST'])]
    public function updateMe(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

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

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'photo' => $user->getPhoto() ? '/uploads/users/'.$user->getPhoto() : null,
            ]
        ]);
    }
}
