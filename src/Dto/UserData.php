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


  public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

     public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }
}
