<?php

namespace App\Auth;

use Framework\Core\IIdentity;

/**
 * Class UserIdentity
 *
 * Reprezentuje identitu prihláseného používateľa. Obsahuje základné informácie ako ID, email, meno a rolu.
 * Implementuje rozhranie IIdentity použité vo frameworku.
 *
 * @package App\Auth
 */
class UserIdentity implements IIdentity
{
    private int $id;
    private string $email;
    private ?string $name;
    private ?string $role;

    /**
     * UserIdentity constructor.
     *
     * @param int $id Primárne ID používateľa z databázy
     * @param string $email Email používateľa
     * @param string|null $name Voliteľné celé meno (meno + priezvisko)
     * @param string|null $role Voliteľná rola používateľa (napr. 'admin', 'atlet')
     */
    public function __construct(int $id, string $email, ?string $name = null, ?string $role = null)
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->role = $role;
    }

    /**
     * Vráti ID používateľa.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Vráti email používateľa.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Vráti zobrazované meno používateľa (ak nie je nastavené, vráti email ako fallback).
     *
     * @return string
     */
    public function getName(): string
    {
        if (!empty($this->name)) {
            return $this->name;
        }
        return $this->email;
    }

    /**
     * Vráti rolu používateľa (alebo null, ak nie je nastavená).
     *
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }
}

