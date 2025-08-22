<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserData
{
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    public ?string $name = null;

    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    public ?string $email = null;

    #[Assert\NotBlank(message: "Le rôle est obligatoire")]
    #[Assert\All([
        new Assert\Choice(['ROLE_USER', 'ROLE_ADMIN'], message: "Rôle invalide")
    ])]
    public array $roles = [];

    #[Assert\NotBlank(
        message: "Le mot de passe est obligatoire",
        groups: ['create']
    )]
    #[Assert\Length(
        min: 6,
        minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères",
        groups: ['create']
    )]
    public ?string $password = null;

    public ?bool $active = true;

    public ?\Symfony\Component\HttpFoundation\File\UploadedFile $photo = null;
}
