<?php

namespace App\Models;

use Framework\Core\Model;

class Album extends Model
{
    protected static ?string $tableName = 'albums';

    public function __construct(
        protected ?int $id = null,
        protected string $text = '',
        protected string $picture = ''
    )
    {

    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function setPicture(string $picture): void
    {
        $this->picture = $picture;
    }
}

