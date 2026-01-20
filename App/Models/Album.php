<?php

namespace App\Models;

use Framework\Core\Model;

/**
 * Model Albumu reprezentujúci záznam v tabuľke `albums`.
 *
 * Použitie: jednoduchý dátový kontajner pre albumy.
 *
 * @package App\Models
 * @property int|null $id ID albumu
 * @property string $text Popis albumu
 * @property string $picture Cesta k obrázku (názov súboru)
 * @property string|null $datum_vytvorenia Dátum vytvorenia v DB
 */
class Album extends Model
{
    protected static ?string $tableName = 'albums';

    public function __construct(
        protected ?int $id = null,
        protected string $text = '',
        protected string $picture = '',
        protected ?string $datum_vytvorenia = null
    )
    {

    }

    /** Získa dátum vytvorenia albumu. */
    public function getDatumVytvorenie(): ?string
    {
        return $this->datum_vytvorenia;
    }

    /** Získa ID albumu. */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** Získa text/popis albumu. */
    public function getText(): string
    {
        return $this->text;
    }

    /** Získa názov obrázku (picture). */
    public function getPicture(): string
    {
        return $this->picture;
    }

    /** Nastaví ID albumu. */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /** Nastaví text albumu. */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /** Nastaví názov obrázku albumu. */
    public function setPicture(string $picture): void
    {
        $this->picture = $picture;
    }
}

