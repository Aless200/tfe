<?php

// src/Model/Arbitrator.php

namespace App\Class;

class Arbitrator
{
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
