<?php

namespace App\Models;

use Framework\Core\Model;

class Training extends Model
{
    protected static ?string $tableName = 'trainings';

    public function __construct(
        protected ?int $id = null,
        protected string $den = '',
        protected ?string $cas_zaciatku = null,
        protected ?string $cas_konca = null,
        protected ?string $popis = null
    ) {

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDen(): string
    {
        return $this->den;
    }

    public function getCasZaciatku(): ?string
    {
        return $this->cas_zaciatku;
    }

    public function getCasKonca(): ?string
    {
        return $this->cas_konca;
    }

    public function getPopis(): ?string
    {
        return $this->popis;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setDen(string $den): void
    {
        $this->den = $den;
    }

    public function setCasZaciatku(?string $cas): void
    {
        $this->cas_zaciatku = $cas;
    }

    public function setCasKonca(?string $cas): void
    {
        $this->cas_konca = $cas;
    }

    public function setPopis(?string $popis): void
    {
        $this->popis = $popis;
    }

}
