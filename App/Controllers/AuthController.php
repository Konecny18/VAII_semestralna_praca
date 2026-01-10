<?php

namespace App\Controllers;

use App\Configuration;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;
use Framework\DB\Connection;
use PDOException;

/**
 * Class AuthController
 *
 * Rieši autentifikáciu používateľov: prihlásenie, odhlásenie a registráciu. Obsahuje aj AJAX endpoint
 * na rýchlu kontrolu dostupnosti emailu pri registrácii.
 *
 * @package App\Controllers
 */
class AuthController extends BaseController
{
    /**
     * Presmeruje na prihlasovaciu stránku definovanú v konfigurácii.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->redirect(Configuration::LOGIN_URL);
    }

    /**
     * Spracuje pokus o prihlásenie. Pri úspechu presmeruje používateľa na domovskú stránku,
     * pri neúspechu vráti chybovú správu do view.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function login(Request $request): Response
    {
        $logged = null;
        $message = null;

        if ($request->hasValue('submit')) {
            // Use email as the credential field (not username)
            $email = mb_strtolower(trim((string)$request->value('email')));
            $password = (string)$request->value('password');

            if ($email === '' || $password === '') {
                $message = 'Zadajte email a heslo.';
            } else {
                // Try to authenticate via configured authenticator (DbAuthenticator)
                // DbAuthenticator treats the first parameter as email
                $logged = $this->app->getAuthenticator()->login($email, $password);
                if ($logged) {
                    return $this->redirect($this->url("home.index"));
                }

                // If authentication failed, probe the database to give more specific feedback.
                try {
                    $conn = Connection::getInstance();
                    // Probe DB for the user and available password columns to give a better error message
                    $stmt = $conn->prepare('SELECT password FROM users WHERE email = :email LIMIT 1');
                    $stmt->execute([':email' => $email]);
                    $row = $stmt->fetch();
                    if (!$row) {
                        $message = 'Používateľ s týmto emailom neexistuje.';
                    } else {
                        $hash = $row['password'] ?? $row['password_hash'] ?? null;
                        if ($hash === null) {
                            $message = 'Nesprávne prihlasovacie údaje.';
                        } elseif (!password_verify($password, $hash)) {
                            $message = 'Nesprávne heslo.';
                        } else {
                            // This branch should be unreachable because authenticator already tried, but keep fallback
                            $message = 'Nesprávne prihlasovacie údaje.';
                        }
                    }
                } catch (PDOException $e) {
                    // Do not expose DB internals; show a user-friendly message
                    $message = 'Chyba pri prístupe do databázy. Skúste to neskôr.';
                }
            }
        }

        return $this->html(compact('message'));
    }

    /**
     * Odhlási aktuálneho používateľa a vráti view (alebo redirect podľa potreby).
     *
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request): Response
    {
        $this->app->getAuthenticator()->logout();
        return $this->html();
    }

    /**
     * Registrácia nového používateľa. Pri POST validuje vstupy a vloží nový záznam do DB.
     *
     * @param Request $request
     * @return Response
     */
    public function register(Request $request): Response
    {
        $errors = [];
        $old = [
            'meno' => '',
            'priezvisko' => '',
            'email' => ''
        ];

        if ($request->isPost()) {
            //strip_tags pre ochranu XSS aby tam niekto nemohol dat <script>alert(1)</script>.
            $meno = strip_tags(trim((string)$request->post('meno') ?? ''));
            $priezvisko = strip_tags(trim((string)$request->post('priezvisko') ?? ''));
            // normalize email to lowercase for consistent storage and lookup
            $email = mb_strtolower(trim((string)$request->post('email') ?? ''));
            $password = (string)($request->post('password') ?? '');
            $passwordConfirm = (string)($request->post('password_confirm') ?? '');

            $old = ['meno' => $meno, 'priezvisko' => $priezvisko, 'email' => $email];

            // Validation
            if ($meno === '') {
                $errors[] = 'Meno je povinné.';
            }
            if ($priezvisko === '') {
                $errors[] = 'Priezvisko je povinné.';
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Platný email je povinný.';
            }
            if (strlen($password) < 6) {
                $errors[] = 'Heslo musí mať aspoň 6 znakov.';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Heslá sa nezhodujú.';
            }

            // check email uniqueness
            if (empty($errors)) {
                try {
                    $conn = Connection::getInstance();
                    $stmt = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                    $stmt->execute([':email' => $email]);
                    $exists = $stmt->fetch();
                    if ($exists) {
                        $errors[] = 'Email je už registrovaný.';
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Chyba pri kontrole emailu: ' . $e->getMessage();
                }
            }

            if (empty($errors)) {
                // determine role
                $isAdmin = (
                    $meno === 'Damián' && $priezvisko === 'Konečný' &&
                    mb_strtolower($email) === mb_strtolower('damkokonecny@gmail.com')
                );
                $rola = $isAdmin ? 'admin' : 'atlet';

                // hash password
                $hash = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $conn = Connection::getInstance();
                    $ins = $conn->prepare('INSERT INTO users (meno, priezvisko, email, password, rola) VALUES (:meno, :priezvisko, :email, :hash, :rola)');
                    $ins->execute([
                        ':meno' => $meno !== '' ? $meno : null,
                        ':priezvisko' => $priezvisko !== '' ? $priezvisko : null,
                        ':email' => $email,
                        ':hash' => $hash,
                        ':rola' => $rola
                    ]);

                    // redirect to login page on success
                    return $this->redirect(Configuration::LOGIN_URL);
                } catch (PDOException $e) {
                    $errors[] = 'Chyba pri registrácii: ' . $e->getMessage();
                }
            }
        }

        return $this->html(compact('errors', 'old'));
    }

    /**
     * AJAX endpoint to check if an email is already registered.
     * Returns JSON: { success: true, exists: bool }
     *
     * @param Request $request
     * @return Response
     */
    public function checkEmail(Request $request): Response
    {
        $email = mb_strtolower(trim((string)$request->value('email') ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['success' => false, 'message' => 'Neplatný email.']);
        }

        try {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $exists = (bool)$stmt->fetch();
            return $this->json(['success' => true, 'exists' => $exists]);
        } catch (PDOException $e) {
            return $this->json(['success' => false, 'message' => 'Chyba pri dotaze do DB.']);
        }
    }
}
