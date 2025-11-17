<?php

namespace App\Models;

use Framework\Core\Model;

class Post extends Model
{
    // Explicitly set the DB table name (follows your SQL snippet name). If your table name differs, change it here.
    protected static ?string $tableName = 'posts';

    public function __construct(
        protected ?int $id = null,
        protected ?int $albumId = null,
        protected string $text = '',
        protected string $picture = ''
    )
    {

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlbumId(): ?int
    {
        return $this->albumId;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getPicture(): string
    {
        return $this->picture;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setAlbumId(?int $albumId): void
    {
        $this->albumId = $albumId;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function setPicture(string $picture): void
    {
        $this->picture = $picture;
    }




}