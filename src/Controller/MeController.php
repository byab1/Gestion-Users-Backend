<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    #[Route('', name: 'me_update', methods: ['PUT', 'POST'])]
    public function updateMe(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->getContentTypeFormat() === 'json') {
            $data = json_decode($request->getContent(), true);
            if (isset($data['name'])) $user->setName($data['name']);
            if (isset($data['email'])) $user->setEmail($data['email']);
            if (!empty($data['password'])) {
                $user->setPassword($hasher->hashPassword($user, $data['password']));
            }
        } else {

            /** @var UploadedFile $file */
            $file = $request->files->get('photo');
            // dd($file);
            if ($file) {
                $user->setPhotoFile($file);
            }

            if ($request->request->get('name')) $user->setName($request->request->get('name'));
            if ($request->request->get('email')) $user->setEmail($request->request->get('email'));
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
