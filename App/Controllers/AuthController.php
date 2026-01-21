<?php

namespace App\Controllers;

use App\Configuration;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

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
            // email na je malé písmená pre konzistentné ukladanie a vyhľadávanie
            $email = mb_strtolower(trim((string)$request->value('email')));
            $password = (string)$request->value('password');

            if ($email === '' || $password === '') {
                $message = 'Zadajte email a heslo.';
            } else {
                //ak su udaje spravne tak vytvori session a presmeruje na home page
                $logged = $this->app->getAuthenticator()->login($email, $password);
                if ($logged) {
                    return $this->redirect($this->url("home.index"));
                }

                // If authentication failed, probe the database to give more specific feedback.
                try {
                    $conn = Connection::getInstance();
                    // prejdem databazu a skusim najst usera s danym emailom
                    $stmt = $conn->prepare('SELECT password FROM users WHERE email = :email LIMIT 1');
                    $stmt->execute([':email' => $email]);
                    $row = $stmt->fetch();
                    // ak sa nenasiel ziadny user s tymto emailom
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
                } catch (PDOException) {
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
        // odhlási používateľa
        // zavola autenticator ktory zmaze session a udaje o prihlasenom userovi
        $this->app->getAuthenticator()->logout();
        return $this->html();
    }

    /**
     * Registrácia nového používateľa. Pri POST validuje vstupy a vloží nový záznam do DB.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function register(Request $request): Response
    {
        //pole na ukladanie chybovych hlások
        $errors = [];
        //pole na uchovanie starých hodnôt formulára pre prípad chyby
        $old = [
            'meno' => '',
            'priezvisko' => '',
            'email' => ''
        ];

        // vykona sa iba vtedy ak bol formular odoslany metodou POST
        if ($request->isPost()) {
            // OSETRENIE VSTUPOV (BEZPECNOST)

            // strip_tags: odstráni HTML značky (ochrana proti XSS útokom)
            // trim: odstráni medzery na začiatku a konci (prevencia preklepov)
            $meno = strip_tags(trim((string)$request->post('meno') ?? ''));
            $priezvisko = strip_tags(trim((string)$request->post('priezvisko') ?? ''));

            // mb_strtolower: premení email na malé písmená (v DB chceme mať konzistenciu)
            $email = mb_strtolower(trim((string)$request->post('email') ?? ''));
            $password = (string)($request->post('password') ?? '');
            $passwordConfirm = (string)($request->post('password_confirm') ?? '');

            // Uložíme vyčistené dáta späť, aby sme ich mohli vrátiť do formulára pri chybe
            $old = ['meno' => $meno, 'priezvisko' => $priezvisko, 'email' => $email];

            /* --- 2. VALIDÁCIA ÚDAJOV --- */
            if ($meno === '') {
                $errors[] = 'Meno je povinné.';
            }
            if ($priezvisko === '') {
                $errors[] = 'Priezvisko je povinné.';
            }

            // Filter_var: overí, či má email správny formát (napr. či obsahuje @ a doménu)
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Platný email je povinný.';
            }

            /* --- 3. KONTROLA SILY HESLA --- */

            // 1. Dĺžka (aspoň 8 znakov)
            if (strlen($password) < 8) {
                $errors[] = 'Heslo musí mať aspoň 8 znakov.';
            }
            // 2. Komplexnosť (RegEx)
            else {
                // Regulárny výraz (RegEx) na kontrolu komplexnosti hesla:
                // Musí obsahovať: veľké písmeno, malé písmeno, číslo a špeciálny znak
                $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.])[A-Za-z\d@$!%*?&.]{8,}$/';

                if (!preg_match($pattern, $password)) {
                    $errors[] = 'Heslo je príliš slabé. Musí obsahovať veľké písmeno, malé písmeno, číslo a špeciálny znak (@$!%*?&.).';
                }
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Heslá sa nezhodujú.';
            }

            /* --- 4. KONTROLA UNIKÁTNOSTI EMAILU (Databáza) --- */
            if (empty($errors)) {
                try {
                    $conn = Connection::getInstance();
                    // SQL injekcia: Používame prepare/execute na bezpečné vkladanie premenných
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

            /* --- 5. FINÁLNE ULOŽENIE REGISTRÁCIE --- */
            if (empty($errors)) {
                // Logika priradenia roly: Ak sa registrujem, tak som Admin, ostatní sú Atleti
                $isAdmin = (
                    $meno === 'Damián' && $priezvisko === 'Konečný' &&
                    mb_strtolower($email) === mb_strtolower('damkokonecny@gmail.com')
                );
                $rola = $isAdmin ? 'admin' : 'atlet';

                // password_hash: Nikdy neukladáme čisté heslo! Používame silný hashovací algoritmus.
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

                    // Po úspešnej registrácii presmerujeme používateľa na prihlásenie
                    return $this->redirect(Configuration::LOGIN_URL);
                } catch (PDOException $e) {
                    $errors[] = 'Chyba pri registrácii: ' . $e->getMessage();
                }
            }
        }

        // Ak formulár nebol odoslaný alebo nastali chyby, vrátime zobrazenie formulára (view) s chybami a pôvodnými dátami
        return $this->html(compact('errors', 'old'));
    }

    /**
     * AJAX endpoint to check if an email is already registered.
     * Returns JSON: { success: true, exists: bool }
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function checkEmail(Request $request): Response
    {
        /* --- 1. PRÍPRAVA A OČISTENIE VSTUPU --- */
        // Získame email z GET/POST parametrov, orežeme medzery a zmeníme na malé písmená
        $email = mb_strtolower(trim((string)$request->value('email') ?? ''));

        // Základná validácia formátu emailu priamo v PHP predtým, než pôjdeme do DB
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Ak je email zlý, vrátime JSON odpoveď s chybou
            return $this->json(['success' => false, 'message' => 'Neplatný email.']);
        }

        /* --- 2. DOTAZ DO DATABÁZY --- */
        try {
            $conn = Connection::getInstance();

            // Hľadáme ID používateľa, ktorý má tento email (LIMIT 1 stačí na overenie existencie)
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);

            // fetch() vráti riadok ak existuje, alebo false ak neexistuje
            // Pretypujeme výsledok na (bool), takže dostaneme true (obsadený) alebo false (voľný)
            $exists = (bool)$stmt->fetch();

            /* --- 3. ODOSLANIE ODPOVEDE PRE JAVASCRIPT --- */
            // Vraciame úspešný stav a informáciu o tom, či email v DB existuje
            return $this->json(['success' => true, 'exists' => $exists]);
        } catch (PDOException) {
            // V prípade výpadku DB alebo chyby v SQL vrátime false, aby JS vedel, že nastala technická chyba
            return $this->json(['success' => false, 'message' => 'Chyba pri dotaze do DB.']);
        }
    }
}
