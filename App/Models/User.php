<?php

namespace App\Models;

use Framework\Core\Model;

class User extends Model
{
    protected static ?string $tableName = 'users';

    public function __construct(
        protected ?int $id = null,
        protected ?string $meno = null,
        protected ?string $priezvisko = null,
        protected string $email = '',
        protected string $password = '',
        protected string $rola = 'atlet',
        protected ?string $datum_registracie = null
    ) {
    }

    public function getId(): ?int { return $this->id; }
    public function getMeno(): ?string { return $this->meno; }
    public function getPriezvisko(): ?string { return $this->priezvisko; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRole(): string { return $this->rola; }
    public function getDatumRegistracie(): ?string { return $this->datum_registracie; }

    public function setRole(string $role): void { $this->rola = $role; }
    public function setPassword(string $hash): void { $this->password = $hash; }
}
