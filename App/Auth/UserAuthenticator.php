<?php

namespace App\Auth;

use Framework\Auth\SessionAuthenticator;
use Framework\Core\IIdentity;
use Framework\DB\Connection;

class UserAuthenticator extends SessionAuthenticator
{
    /**
     * Authenticate a user by email and password against the `users` table.
     * The method expects the first parameter to be the email (lowercased by the controller).
     * Returns an IIdentity on success or null on failure.
     */
    protected function authenticate(string $username, string $password): ?IIdentity
    {
        $email = mb_strtolower(trim($username));
        if ($email === '' || $password === '') {
            return null;
        }

        try {
            $conn = Connection::getInstance();
            // select both possible password columns to be compatible with different DDLs
            $sql = 'SELECT id, meno, priezvisko, email, password, rola FROM users WHERE email = :email LIMIT 1';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            // use the single `password` column present in the users table
            if (!isset($row['password']) || $row['password'] === null) {
                // no password column/value present, fail authentication
                return null;
            }
            $hash = $row['password'];

            if (!password_verify($password, $hash)) {
                return null;
            }

            $fullName = trim((string)($row['meno'] ?? '') . ' ' . (string)($row['priezvisko'] ?? ''));
            $name = $fullName !== '' ? $fullName : $row['email'];

            return new UserIdentity((int)$row['id'], $row['email'], $name, $row['rola'] ?? null);
        } catch (\Throwable $e) {
            // Do not expose DB errors to caller; authentication simply fails
            return null;
        }
    }

    public function isLoggedIn(): bool
    {
        // 1. Skontrolujeme, či je používateľ vôbec prihlásený
        if (!$this->getUser()->isLoggedIn()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Zistí, či je aktuálne prihlásený používateľ administrátor.
     * @return bool
     */
    public function isAdmin(): bool
    {
        // 1. Skontrolujeme, či je používateľ vôbec prihlásený
        if (!$this->getUser()->isLoggedIn()) {
            return false;
        }

        // 2. Získame identitu prihláseného používateľa
        //$identity = $this->getUser()->getIdentity();
        $rola = $this->getUser()->getRole();

        // 3. Ak identita existuje, skontrolujeme rolu (UserIdentity ju ukladá v konštruktore)
        // Používame nullsafe operátor ?-> pre istotu
        if ($rola === 'admin') {
            return true;
        } else {
            return false;
        }
    }
}
