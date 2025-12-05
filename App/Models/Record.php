<?php

namespace App\Models;

use Framework\Core\Model;

/**
 * Record model representing a performance record linked to a user.
 */
class Record extends Model
{
    protected static ?string $tableName = 'records';

    public function __construct(
        protected ?int $id = null,
        protected int $user_id = 0,
        protected string $nazov_discipliny = '',
        protected ?string $dosiahnuty_vykon = null,
        protected ?string $datum_vykonu = null,
        protected ?string $poznamka = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getNazovDiscipliny(): string
    {
        return $this->nazov_discipliny;
    }

    public function getDosiahnutyVykon(): ?string
    {
        return $this->dosiahnuty_vykon;
    }

    public function getDatumVykonu(): ?string
    {
        return $this->datum_vykonu;
    }

    public function getPoznamka(): ?string
    {
        return $this->poznamka;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setUserId(int $userId): void
    {
        $this->user_id = $userId;
    }

    public function setNazovDiscipliny(string $nazov): void
    {
        $this->nazov_discipliny = $nazov;
    }

    public function setDosiahnutyVykon(?string $vykon): void
    {
        $this->dosiahnuty_vykon = $vykon;
    }

    public function setDatumVykonu(?string $datum): void
    {
        $this->datum_vykonu = $datum;
    }

    public function setPoznamka(?string $poznamka): void
    {
        $this->poznamka = $poznamka;
    }
}
