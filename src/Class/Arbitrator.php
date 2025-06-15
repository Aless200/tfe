<?php

// src/Model/Arbitrator.php

namespace App\Class;

use Symfony\Component\Validator\Constraints as Assert;

class Arbitrator
{
    #[Assert\NotBlank(
        message: 'Veuillez remplir le nom.',
    )]
    #[Assert\length(
        min: 2,
        max: 120,
        minMessage: 'Le nom doit contenir au minimum {{ limit }} caractères.',
        maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.'
    )]
    private $username;
    private $password;
    private $createdAt;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
