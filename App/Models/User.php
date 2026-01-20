<?php

namespace App\Models;

use Framework\Core\Model;

/**
 * Model používateľa (users).
 *
 * Reprezentuje záznam v tabuľke `users` a poskytuje základné get/set metódy
 * pre vlastnosti používateľa. Používajte tento model pri práci s údajmi o používateľoch.
 *
 * @package App\Models
 * @property int|null $id Primárne ID používateľa
 * @property string|null $meno Krstné meno používateľa
 * @property string|null $priezvisko Priezvisko používateľa
 * @property string $email Email (používaný pre prihlásenie)
 * @property string $password Hash hesla
 * @property string $rola Rola používateľa (napr. 'atlet', 'trener', 'admin')
 * @property string|null $datum_registracie Dátum registrácie
 */
class User extends Model
{
    protected static ?string $tableName = 'users';

    public function __construct(
        protected ?int $id = null,
        protected ?string $meno = null,
        protected ?string $priezvisko = null,
        protected string $email = '',
        protected string $password = '',
        protected string $rola = 'atlet',
        protected ?string $datum_registracie = null
    ) {
    }

    /**
     * Vracia ID používateľa.
     *
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * Vracia krstné meno používateľa.
     *
     * @return string|null
     */
    public function getMeno(): ?string { return $this->meno; }

    /**
     * Vracia priezvisko používateľa.
     *
     * @return string|null
     */
    public function getPriezvisko(): ?string { return $this->priezvisko; }

    /**
     * Vracia email používateľa.
     *
     * @return string
     */
    public function getEmail(): string { return $this->email; }

    /**
     * Vracia hash hesla používateľa (pozor: nikdy nevracajte čisté heslo).
     *
     * @return string
     */
    public function getPassword(): string { return $this->password; }

    /**
     * Vracia rolu používateľa.
     *
     * @return string
     */
    public function getRole(): string { return $this->rola; }

    /**
     * Vracia dátum registrácie používateľa.
     *
     * @return string|null
     */
    public function getDatumRegistracie(): ?string { return $this->datum_registracie; }

    /**
     * Nastaví rolu používateľa (napr. 'admin', 'trener', 'atlet').
     *
     * @param string $role
     * @return void
     */
    public function setRole(string $role): void { $this->rola = $role; }

    /**
     * Nastaví hash hesla používateľa.
     *
     * Ukladáme iba hash (napr. password_hash()). Nikdy neukladajte čisté heslo.
     *
     * @param string $hash
     * @return void
     */
    public function setPassword(string $hash): void { $this->password = $hash; }
}
