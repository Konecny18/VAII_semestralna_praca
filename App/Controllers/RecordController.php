<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\Record;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\DB\Connection;

class RecordController extends BaseController
{
    public function index(Request $request): Response
    {
        $auth = $this->app->getAuthenticator();
        try {
            // Require login to view records: only the owner or admin can see records
            $appUser = $this->user ?? null;
            if (!$appUser || !method_exists($appUser, 'isLoggedIn') || !$appUser->isLoggedIn()) {
                // Not logged in -> redirect to login
                return $this->redirect(Configuration::LOGIN_URL);
            }

            // Get identity to read id/role
            $identity = $appUser->getIdentity();
            if ($identity === null) {
                return $this->redirect(Configuration::LOGIN_URL);
            }

            $role = method_exists($identity, 'getRole') ? $identity->getRole() : null;

            if ($role === 'admin') {
                // Admin sees all records
                $records = Record::getAll(null, [], 'id DESC');
            } else {
                // Regular user sees only their own records
                $userId = method_exists($identity, 'getId') ? $identity->getId() : null;
                if ($userId === null) {
                    return $this->redirect(Configuration::LOGIN_URL);
                }
                $records = Record::getAll('user_id = :uid', [':uid' => $userId], 'id DESC');
            }

            // prejde vsetky rocordy a ulozi si rozdielne id do pola
            $owners = [];
            $userIds = [];
            foreach ($records as $r) {
                $uid = $r->getUserId();
                if ($uid !== null) $userIds[$uid] = $uid;
            }
            if (!empty($userIds)) {
                try {
                    $conn = Connection::getInstance();
                    //array fill spravi pole a implode spravy retazec z toho, dynamicky retazec naplneny otaznikmy
                    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                    //vytiahne z databazy udaje
                    $sql = "SELECT id, meno, priezvisko FROM users WHERE id IN ($placeholders)";
                    //pockaj na data najskor sa poslu tie otazniky (kvoli bezpecnosti)
                    $stmt = $conn->prepare($sql);
                    //poslu sa skutocne id namiesto tych otaznikov
                    $stmt->execute(array_values($userIds));
                    //stiahne vsetky vysledky z tabulky users
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    foreach ($rows as $row) {
                        // Keďže sú meno a priezvisko povinné, len ich spojím
                        $owners[(int)$row['id']] = $row['meno'] . ' ' . $row['priezvisko'];
                    }
                } catch (\Throwable $e) {
                    // on DB error, leave owners empty — view will fallback to user id
                    $owners = [];
                }
            }

            return $this->html([
                'records' => $records,
                'owners' => $owners,
                'auth' => $auth
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
    }

    public function add(Request $request): Response
    {
        // Require login to add a new record - redirect to login if not logged in
        if (!$this->user->isLoggedIn()) {
            return $this->redirect(Configuration::LOGIN_URL);
        }
        return $this->html();
    }

    public function edit(Request $request): Response
    {
        $id = (int)$request->value('id');
        $record = Record::getOne($id);
        if (is_null($record)) {
            throw new HttpException(404);
        }
        // Only owner or admin can edit
        $identity = $this->user->getIdentity();
        $role = $identity?->getRole() ?? null;
        $userId = $identity?->getId() ?? null;
        if ($role !== 'admin' && $userId !== $record->getUserId()) {
            throw new HttpException(403, 'Nemáte oprávnenie upravovať tento záznam.');
        }

        return $this->html(compact('record'), 'edit');
    }

    public function save(Request $request): Response
    {
        // --- 1. Inicializácia a Sanitizácia ---
        $idRaw = $request->post('id') ?? null;
        $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
        $isEdit = !empty($id);

        // Zásadná SANITIZÁCIA (XSS ochrana)
        $nazov = strip_tags(trim((string)($request->post('nazov_discipliny') ?? '')));
        $vykon = strip_tags(trim((string)($request->post('dosiahnuty_vykon') ?? '')));
        $datumRaw = trim((string)($request->post('datum_vykonu') ?? '')); // Dátum nesanitizujeme tagmi
        $poznamka = strip_tags(trim((string)($request->post('poznamka') ?? '')));

        $errors = [];

        // --- 2. Validácia (Volanie novej metódy) ---
        // Posielame surové/sanitizované dáta, ktoré sa budú validovať.
        $formErrors = $this->formErrors($nazov, $vykon, $datumRaw, $poznamka, $isEdit);

        if (count($formErrors) > 0) {
            // Ak validácia zlyhala, pripravíme Model pre re-populáciu formulára
            $record = ($isEdit) ? Record::getOne($id) : new Record();

            // Nastavíme hodnoty, aby sa zobrazili vo formulári
            $record->setNazovDiscipliny($nazov);
            $record->setDosiahnutyVykon($vykon ?: null);
            $record->setDatumVykonu($datumRaw ?: null);
            $record->setPoznamka($poznamka ?: null);

            // Zabezpečíme, že na editácii zostane user_id, ak validácia zlyhala
            if ($isEdit && $record->getUserId() === 0) {
                // Ak id existuje, ale record ho stratil, musíme ho znovu načítať
                $existing = Record::getOne($id);
                if ($existing) $record->setUserId($existing->getUserId());
            }

            return $this->html(
                ['errors' => $formErrors, 'record' => $record], $isEdit ? 'edit' : 'add'
            );
        }

        // --- 3. Spracovanie a Uloženie (Iba ak je validácia úspešná) ---
        try {
            if ($isEdit) {
                $record = Record::getOne($id);
                if (is_null($record)) {
                    throw new \Exception('Záznam neexistuje.');
                }
                // Kontrola AUTORIZÁCIE: Len majiteľ alebo admin môže editovať
                $identity = $this->user->getIdentity();
                $role = $identity?->getRole() ?? null;
                $userId = $identity?->getId() ?? null;
                if ($role !== 'admin' && $userId !== $record->getUserId()) {
                    // Vraciame 403, namiesto uloženia chyby do poľa
                    throw new HttpException(403, 'Nemáte oprávnenie upravovať tento záznam.');
                }

                $record->setNazovDiscipliny($nazov);
                $record->setDosiahnutyVykon($vykon ?: null);
                $record->setDatumVykonu($datumRaw ?: null);
                $record->setPoznamka($poznamka ?: null);
            } else {
                // Vytváranie nového záznamu
                if (!$this->user->isLoggedIn()) {
                    return $this->redirect(Configuration::LOGIN_URL);
                }

                // Získame ID prihláseného používateľa (autorizácia)
                $identity = $this->user->getIdentity();
                $userId = method_exists($identity, 'getId') ? $identity->getId() : 0;
                if ($userId === 0) {
                    throw new \Exception('Prihlásený používateľ nemá platné ID.');
                }

                $record = new Record(null, (int)$userId, $nazov, $vykon ?: null, $datumRaw ?: null, $poznamka ?: null);
            }

            $record->save();
            return $this->redirect($this->url('record.index'));

        } catch (HttpException $e) {
            // Znovu vyvolanie pre 403 chybu
            throw $e;
        } catch (\Throwable $e) {
            // Zachytenie DB a iných chýb
            $errors[] = 'Nepodarilo sa uložiť záznam: ' . $e->getMessage();

            // Ak bola chyba, vrátime sa do formulára s chybou
            $record->setNazovDiscipliny($nazov);
            $record->setDosiahnutyVykon($vykon ?: null);
            $record->setDatumVykonu($datumRaw ?: null);
            $record->setPoznamka($poznamka ?: null);

            return $this->html(
                ['errors' => $errors, 'record' => $record], $isEdit ? 'edit' : 'add'
            );
        }
    }

    public function delete(Request $request): Response
    {
        try {
            $id = (int)$request->value('id');
            $record = Record::getOne($id);
            if (is_null($record)) {
                throw new HttpException(404);
            }
            // Only owner or admin can delete
            $identity = $this->user->getIdentity();
            $role = $identity?->getRole() ?? null;
            $userId = $identity?->getId() ?? null;
            if ($role !== 'admin' && $userId !== $record->getUserId()) {
                throw new HttpException(403, 'Nemáte oprávnenie zmazať tento záznam.');
            }

            $record->delete();
        } catch (\Exception $e) {
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }

        return $this->redirect($this->url('record.index'));
    }

    private function formErrors(string $nazov, string $vykon, string $datumRaw, string $poznamka, bool $isEdit): array
    {
        $errors = [];
        $maxTextLength = 255;
        $minNazovLength = 2;

        // --- 1. Názov disciplíny (VARCHAR(255) NOT NULL) ---
        if ($nazov === '') {
            $errors[] = 'Názov disciplíny je povinný.';
        } elseif (mb_strlen($nazov) < $minNazovLength) {
            $errors[] = 'Názov disciplíny musí mať aspoň ' . $minNazovLength . ' znaky.';
        } elseif (mb_strlen($nazov) > $maxTextLength) {
            $errors[] = 'Názov disciplíny nesmie presiahnuť ' . $maxTextLength . ' znakov.';
        }

        // --- 2. Dátum výkonu (TIMESTAMP NOT NULL) ---
        if ($datumRaw === '') {
            $errors[] = 'Dátum výkonu je povinný.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datumRaw)) {
            $errors[] = 'Dátum má nesprávny formát (očakáva sa YYYY-MM-DD).';
        } else {
            $timestamp = strtotime($datumRaw);
            $today = strtotime(date('Y-m-d'));

            if (!$timestamp) {
                $errors[] = 'Zadaný dátum je neplatný.';
            } elseif ($timestamp > $today) {
                $errors[] = 'Dátum výkonu nemôže byť v budúcnosti.';
            }
        }

        // --- 3. Dosiahnutý výkon (VARCHAR(255) DEFAULT NULL) ---
        if ($vykon !== '' && mb_strlen($vykon) > $maxTextLength) {
            $errors[] = 'Dosiahnutý výkon nesmie presiahnuť ' . $maxTextLength . ' znakov.';
        }

        // --- 4. Poznámka (VARCHAR(255) DEFAULT NULL) ---
        if ($poznamka !== '' && mb_strlen($poznamka) > $maxTextLength) {
            $errors[] = 'Poznámka nesmie presiahnuť ' . $maxTextLength . ' znakov.';
        }

        return $errors;
    }
}
