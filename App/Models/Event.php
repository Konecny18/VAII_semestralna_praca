<?php

namespace App\Models;

use Framework\Core\Model;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNazov(): ?string
    {
        return $this->nazov;
    }

    public function getPlagat(): ?string
    {
        return $this->plagat;
    }

    public function getPopis(): ?string
    {
        return $this->popis;
    }

    public function getLinkPrihlasovanie(): ?string
    {
        return $this->link_prihlasovanie;
    }

    public function getDokumentPropozicie(): ?string
    {
        return $this->dokument_propozicie;
    }

    public function getDatumPodujatia(): ?string
    {
        return $this->datum_podujatia;
    }

    public function getVytvoreneAt(): ?string
    {
        return $this->vytvorene_at;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setNazov(string $nazov): void
    {
        $this->nazov = $nazov;
    }

    public function setPlagat(?string $plagat): void
    {
        $this->plagat = $plagat;
    }

    public function setPopis(?string $popis): void
    {
        $this->popis = $popis;
    }

    public function setLinkPrihlasovanie(?string $link): void
    {
        $this->link_prihlasovanie = $link;
    }

    public function setDokumentPropozicie(?string $doc): void
    {
        $this->dokument_propozicie = $doc;
    }

    public function setDatumPodujatia(?string $date): void
    {
        $this->datum_podujatia = $date;
    }

    public function setVytvoreneAt(?string $created): void
    {
        $this->vytvorene_at = $created;
    }
}

