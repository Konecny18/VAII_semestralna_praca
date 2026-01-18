<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\DB\Connection;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use PDOException;

class AdminController extends BaseController
{
    /**
     * Autorizácia: Iba prihlásený admin má prístup k týmto akciám.
     */
    public function authorize(Request $request, string $action): bool
    {
        $appUser = $this->app->getAppUser();
        if (!$appUser->isLoggedIn()) {
            return false;
        }
        $role = $appUser->getIdentity()?->getRole();
        return $role === 'admin';
    }

    public function index(Request $request): Response
    {
        return $this->redirect($this->url('admin.users'));
    }

    /**
     * Zoznam všetkých používateľov.
     */
    public function users(Request $request): Response
    {
        $users = [];
        $error = null;

        try {
            $conn = Connection::getInstance();
            $stmt = $conn->query('SELECT id, meno, priezvisko, email, rola FROM users');
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = 'Chyba pri načítaní používateľov: ' . $e->getMessage();
        }

        return $this->html([
            'users' => $users,
            'user' => $this->app->getAppUser(),
            'error' => $error,
        ]);
    }

    /**
     * Formulár pre úpravu používateľa.
     */
    public function edit(Request $request): Response
    {
        $id = (int)$request->value('id');

        if ($id <= 0) {
            return $this->redirect($this->url('admin.users'));
        }

        try {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare('SELECT id, meno, priezvisko, email, rola FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$userData) {
                return $this->redirect($this->url('admin.users'));
            }

            return $this->html(['userData' => $userData], 'edit');
        } catch (PDOException $e) {
            return $this->redirect($this->url('admin.users'));
        }
    }

    /**
     * Spracovanie úpravy používateľa.
     */
    public function update(Request $request): Response
    {
        $this->validateCsrf($request);
        $id = (int)$request->post('id');

        // 1. PRIPRAVÍME SI DATA HNEĎ NA ZAČIATKU (aby boli dostupné všade)
        $userData = [
            'id' => $id,
            'meno' => $request->post('meno'),
            'priezvisko' => $request->post('priezvisko'),
            'email' => $request->post('email'),
            'rola' => $request->post('role')
        ];

        // 2. Spustíme validáciu
        $errors = $this->formErrors($request, $id);

        if (!empty($errors)) {
            // Ak sú chyby vo validácii, vrátime formulár s chybami
            return $this->html(['userData' => $userData, 'errors' => $errors], 'edit');
        }

        // 3. Ak je všetko OK, pokračujeme v ukladaní (so sanitizáciou)
        $meno = strip_tags(trim((string)$userData['meno']));
        $priezvisko = strip_tags(trim((string)$userData['priezvisko']));
        $email = filter_var(trim((string)$userData['email']), FILTER_SANITIZE_EMAIL);
        $newRole = (string)$userData['rola'];

        // Poistka pre admina (aby sa nezablokoval)
        $currentId = (int)$this->app->getAppUser()->getIdentity()?->getId();
        if ($id === $currentId && $newRole !== 'admin') {
            $newRole = 'admin';
        }

        try {
            $conn = Connection::getInstance();
            $sql = "UPDATE users SET meno = :meno, priezvisko = :priezvisko, email = :email, rola = :rola WHERE id = :id";
            $conn->prepare($sql)->execute([
                ':id' => $id,
                ':meno' => $meno,
                ':priezvisko' => $priezvisko,
                ':email' => $email,
                ':rola' => $newRole,
            ]);
        } catch (PDOException $e) {
            // TERAZ UŽ $userData EXISTUJE, takže catch prebehne v poriadku
            return $this->html(['userData' => $userData, 'errors' => ['Chyba pri zápise do databázy (možný duplicitný email).']], 'edit');
        }

        return $this->redirect($this->url('admin.users'));
    }

    /**
     * Odstránenie používateľa (AJAX-ready).
     */
    public function delete(Request $request): Response
    {
        // Pri AJAX-e framework zvyčajne overuje CSRF cez headery, ale pre istotu:
        $this->validateCsrf($request);

        $id = (int)$request->value('user_id');
        $currentId = (int)$this->app->getAppUser()->getIdentity()?->getId();

        // Ochrana: Admin nemôže zmazať sám seba
        if ($currentId === $id) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Nemôžete zmazať vlastný účet!'], 403);
            }
            return $this->redirect($this->url('admin.users'));
        }

        try {
            $conn = Connection::getInstance();

            // Kontrola existencie
            $check = $conn->prepare('SELECT id FROM users WHERE id = :id');
            $check->execute([':id' => $id]);
            if (!$check->fetch()) {
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Používateľ nebol nájdený.'], 404);
                }
                return $this->redirect($this->url('admin.users'));
            }

            // Samotné zmazanie - ON DELETE CASCADE v DB vymaže aj tabuľku records
            $stmt = $conn->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);

            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }
        } catch (PDOException $e) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'DB Chyba: ' . $e->getMessage()], 500);
            }
        }

        return $this->redirect($this->url('admin.users'));
    }

    private function formErrors(Request $request, int $id = 0): array
    {
        $errors = [];
        $meno = trim((string)$request->post('meno'));
        $priezvisko = trim((string)$request->post('priezvisko'));
        $email = trim((string)$request->post('email'));

        if (strlen($meno) < 2) {
            $errors[] = "Meno musí mať aspoň 2 znaky.";
        }

        if (strlen($priezvisko) < 2) {
            $errors[] = "Priezvisko musí mať aspoň 2 znaky.";
        }

        // Validácia formátu emailu
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Zadaný email nemá správny formát.";
        } else {
            // Kontrola unikátnosti emailu v DB
            try {
                $conn = Connection::getInstance();
                // Hľadáme email, ktorý patrí inému ID (preto id != :id)
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
                $stmt->execute([':email' => $email, ':id' => $id]);
                if ($stmt->fetch()) {
                    $errors[] = "Tento email už používa iný používateľ.";
                }
            } catch (PDOException $e) {
                $errors[] = "Chyba pri kontrole emailu v databáze.";
            }
        }

        return $errors;
    }
}