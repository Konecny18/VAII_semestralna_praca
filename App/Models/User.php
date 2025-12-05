<?php

namespace App\Models;

/**
 * Simple User value object representing an authenticated user.
 */
class User
{
    public function __construct(
        protected ?int $id = null,
        protected string $login = '',
        protected string $name = ''
    ) {
    }
}
