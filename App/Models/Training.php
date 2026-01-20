<?php

namespace App\Models;

use Framework\Core\Model;

/**
 * Model tréningu (training) reprezentujúci plán tréningov.
 *
 * @package App\Models
 * @property int|null $id
 * @property string $den Deň (napr. "Pondelok")
 * @property string|null $cas_zaciatku Čas začiatku
 * @property string|null $cas_konca Čas konca
 * @property string|null $popis Popis tréningu
 */
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

    /** Vracia ID tréningu. */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** Vracia deň. */
    public function getDen(): string
    {
        return $this->den;
    }

    /** Vracia čas začiatku. */
    public function getCasZaciatku(): ?string
    {
        return $this->cas_zaciatku;
    }

    /** Vracia čas konca. */
    public function getCasKonca(): ?string
    {
        return $this->cas_konca;
    }

    /** Vracia popis. */
    public function getPopis(): ?string
    {
        return $this->popis;
    }

    /** Nastaví ID. */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /** Nastaví deň. */
    public function setDen(string $den): void
    {
        $this->den = $den;
    }

    /** Nastaví čas začiatku. */
    public function setCasZaciatku(?string $cas): void
    {
        $this->cas_zaciatku = $cas;
    }

    /** Nastaví čas konca. */
    public function setCasKonca(?string $cas): void
    {
        $this->cas_konca = $cas;
    }

    /** Nastaví popis. */
    public function setPopis(?string $popis): void
    {
        $this->popis = $popis;
    }
}
