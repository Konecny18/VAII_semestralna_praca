<?php

namespace App\Auth;

use Framework\Core\IIdentity;

class UserIdentity implements IIdentity
{
    private int $id;
    private string $email;
    private ?string $name;
    private ?string $role;

    public function __construct(int $id, string $email, ?string $name = null, ?string $role = null)
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->role = $role;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        if (!empty($this->name)) {
            return $this->name;
        }
        return $this->email;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }
}

