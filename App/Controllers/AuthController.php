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
 * This controller handles authentication actions such as login, logout, and redirection to the login page. It manages
 * user sessions and interactions with the authentication system.
 *
 * @package App\Controllers
 */
class AuthController extends BaseController
{
    /**
     * Redirects to the login page.
     *
     * This action serves as the default landing point for the authentication section of the application, directing
     * users to the login URL specified in the configuration.
     *
     * @return Response The response object for the redirection to the login page.
     */
    public function index(Request $request): Response
    {
        return $this->redirect(Configuration::LOGIN_URL);
    }

    /**
     * Authenticates a user and processes the login request.
     *
     * This action handles user login attempts. If the login form is submitted, it attempts to authenticate the user
     * with the provided credentials. Upon successful login, the user is redirected to the admin dashboard.
     * If authentication fails, an error message is displayed on the login page.
     *
     * @return Response The response object which can either redirect on success or render the login view with
     *                  an error message on failure.
     * @throws Exception If the parameter for the URL generator is invalid throws an exception.
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
     * Logs out the current user.
     *
     * This action terminates the user's session and redirects them to a view. It effectively clears any authentication
     * tokens or session data associated with the user.
     *
     * @return ViewResponse The response object that renders the logout view.
     */
    public function logout(Request $request): Response
    {
        $this->app->getAuthenticator()->logout();
        return $this->html();
    }

    /**
     * Registration page and handler.
     * GET -> show registration form
     * POST -> validate and create user
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
            $meno = trim((string)$request->post('meno') ?? '');
            $priezvisko = trim((string)$request->post('priezvisko') ?? '');
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
