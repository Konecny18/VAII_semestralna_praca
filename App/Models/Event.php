<?php

namespace App\Models;

use Framework\Core\Model;

/**
 * Model Eventu reprezentujúci záznam v tabuľke `events`.
 *
 * Polia zodpovedajú stĺpcom v DB: názov, plagát, popis, link na prihlásenie,
 * dokument (propozície), dátum podujatia a timestamp vytvorenia.
 *
 * @package App\Models
 * @property int|null $id
 * @property string|null $nazov
 * @property string|null $plagat
 * @property string|null $popis
 * @property string|null $link_prihlasovanie
 * @property string|null $dokument_propozicie
 * @property string|null $datum_podujatia
 * @property string|null $vytvorene_at
 */
class Event extends Model
{
    // Explicit table name to match your SQL
    protected static ?string $tableName = 'events';

    public function __construct(
        protected ?int $id = null,
        protected ?string $nazov = null,
        protected ?string $plagat = null,
        protected ?string $popis = null,
        protected ?string $link_prihlasovanie = null,
        protected ?string $dokument_propozicie = null,
        protected ?string $datum_podujatia = null,
        protected ?string $vytvorene_at = null
    )
    {

    }

    /** Vracia ID eventu. */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** Vracia názov podujatia. */
    public function getNazov(): ?string
    {
        return $this->nazov;
    }

    /** Vracia názov súboru plagátu. */
    public function getPlagat(): ?string
    {
        return $this->plagat;
    }

    /** Vracia krátky/popisný text podujatia. */
    public function getPopis(): ?string
    {
        return $this->popis;
    }

    /** Vracia link na prihlasovanie (ak existuje). */
    public function getLinkPrihlasovanie(): ?string
    {
        return $this->link_prihlasovanie;
    }

    /** Vracia názov súboru s propozíciami (ak existuje). */
    public function getDokumentPropozicie(): ?string
    {
        return $this->dokument_propozicie;
    }

    /** Vracia dátum podujatia vo formáte z DB. */
    public function getDatumPodujatia(): ?string
    {
        return $this->datum_podujatia;
    }

    /** Vracia timestamp vytvorenia. */
    public function getVytvoreneAt(): ?string
    {
        return $this->vytvorene_at;
    }

    /** Nastaví ID eventu. */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /** Nastaví názov podujatia. */
    public function setNazov(string $nazov): void
    {
        $this->nazov = $nazov;
    }

    /** Nastaví názov/plagat súboru. */
    public function setPlagat(?string $plagat): void
    {
        $this->plagat = $plagat;
    }

    /** Nastaví popis. */
    public function setPopis(?string $popis): void
    {
        $this->popis = $popis;
    }

    /** Nastaví link na prihlasovanie. */
    public function setLinkPrihlasovanie(?string $link): void
    {
        $this->link_prihlasovanie = $link;
    }

    /** Nastaví názov dokumentu propozícií. */
    public function setDokumentPropozicie(?string $doc): void
    {
        $this->dokument_propozicie = $doc;
    }

    /** Nastaví dátum podujatia. */
    public function setDatumPodujatia(?string $date): void
    {
        $this->datum_podujatia = $date;
    }

    /** Nastaví timestamp vytvorenia. */
    public function setVytvoreneAt(?string $created): void
    {
        $this->vytvorene_at = $created;
    }
}

