<?php

namespace App\Models;

use Framework\Core\Model;

/**
 * Model Postu (fotky) patriaci do albumu.
 *
 * @package App\Models
 * @property int|null $id
 * @property int|null $albumId ID nadradeného albumu
 * @property string $picture Názov súboru obrázku
 */
class Post extends Model
{
    // Explicitly set the DB table name (follows your SQL snippet name). If your table name differs, change it here.
    protected static ?string $tableName = 'posts';

    public function __construct(
        protected ?int $id = null,
        protected ?int $albumId = null,
        protected string $picture = ''
    )
    {

    }

    /** Vracia ID záznamu. */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** Vracia ID albumu, ku ktorému patrí post. */
    public function getAlbumId(): ?int
    {
        return $this->albumId;
    }

    /** Vracia názov súboru obrázku. */
    public function getPicture(): string
    {
        return $this->picture;
    }

    /** Nastaví ID záznamu. */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /** Nastaví ID albumu. */
    public function setAlbumId(?int $albumId): void
    {
        $this->albumId = $albumId;
    }

    /** Nastaví názov súboru obrázku. */
    public function setPicture(string $picture): void
    {
        $this->picture = $picture;
    }
}