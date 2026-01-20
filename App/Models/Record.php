<?php

namespace App\Models;

use Framework\Core\Model;

/**
 * Record model representing a performance record linked to a user.
 *
 * @package App\Models
 * @property int|null $id
 * @property int $user_id ID používateľa
 * @property string $nazov_discipliny Názov disciplíny
 * @property string|null $dosiahnuty_vykon Dosiahnutý výkon
 * @property string|null $datum_vykonu Dátum výkonu
 * @property string|null $poznamka Poznámka
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

    /**
     * Vracia ID záznamu.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Vracia ID používateľa, ktorý vlastní tento záznam.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * Vracia názov disciplíny (napr. "100m", "skok do diaľky").
     *
     * @return string
     */
    public function getNazovDiscipliny(): string
    {
        return $this->nazov_discipliny;
    }

    /**
     * Vracia dosiahnutý výkon ako text (možno formátovaný, napr. "10.23s").
     *
     * @return string|null
     */
    public function getDosiahnutyVykon(): ?string
    {
        return $this->dosiahnuty_vykon;
    }

    /**
     * Vracia dátum vykonu vo formáte YYYY-MM-DD (alebo null, ak nie je zadané).
     *
     * @return string|null
     */
    public function getDatumVykonu(): ?string
    {
        return $this->datum_vykonu;
    }

    /**
     * Vracia poznámku pripojenú k záznamu (voliteľné pole).
     *
     * @return string|null
     */
    public function getPoznamka(): ?string
    {
        return $this->poznamka;
    }

    /**
     * Nastaví ID záznamu (väčšinou používané interné pri načítaní z DB).
     *
     * @param int|null $id
     * @return void
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Nastaví ID používateľa (vlastníka záznamu).
     *
     * @param int $userId
     * @return void
     */
    public function setUserId(int $userId): void
    {
        $this->user_id = $userId;
    }

    /**
     * Nastaví názov disciplíny.
     *
     * @param string $nazov
     * @return void
     */
    public function setNazovDiscipliny(string $nazov): void
    {
        $this->nazov_discipliny = $nazov;
    }

    /**
     * Nastaví dosiahnutý výkon (voliteľné).
     *
     * @param string|null $vykon
     * @return void
     */
    public function setDosiahnutyVykon(?string $vykon): void
    {
        $this->dosiahnuty_vykon = $vykon;
    }

    /**
     * Nastaví dátum vykonu vo formáte YYYY-MM-DD (alebo null).
     *
     * @param string|null $datum
     * @return void
     */
    public function setDatumVykonu(?string $datum): void
    {
        $this->datum_vykonu = $datum;
    }

    /**
     * Nastaví poznámku k záznamu (voliteľné).
     *
     * @param string|null $poznamka
     * @return void
     */
    public function setPoznamka(?string $poznamka): void
    {
        $this->poznamka = $poznamka;
    }
}
