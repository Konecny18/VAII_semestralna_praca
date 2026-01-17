<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\Record;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\DB\Connection;

/**
 * Class RecordController
 *
 * Spravuje CRUD operácie pre výkony (records). Obsahuje kontrolu oprávnení (iba majiteľ alebo admin môže upravovať / mazať),
 * prehliadanie záznamov a ich validáciu pred uložením.
 *
 * @package App\Controllers
 */
class RecordController extends BaseController
{
    /**
     * Zobrazí zoznam záznamov. Admin vidí všetky záznamy; bežný používateľ len svoje.
     *
     * @param Request $request
     * @return Response
     */
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

    /**
     * Zobrazí formulár pre pridanie nového záznamu. Vyžaduje prihlásenie.
     *
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        // Require login to add a new record - redirect to login if not logged in
        if (!$this->user->isLoggedIn()) {
            return $this->redirect(Configuration::LOGIN_URL);
        }
        return $this->html();
    }

    /**
     * Zobrazí formulár na úpravu záznamu (len majiteľ alebo admin).
     *
     * @param Request $request
     * @return Response
     * @throws HttpException ak záznam neexistuje alebo nie je oprávnenie
     */
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

    /**
     * Uloží nový alebo upravený záznam. Vykonáva server-side validáciu vstupov.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     */

    public function save(Request $request): Response
    {
        // CSRF first
        $this->validateCsrf($request);

        // Require login
        if (!$this->user->isLoggedIn()) {
            return $this->redirect(Configuration::LOGIN_URL);
        }

        // Initialize
        $errors = [];
        $record = null;

        $idRaw = $request->post('id') ?? null;
        $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
        $isEdit = !empty($id);

        // Sanitization
        $nazov = strip_tags(trim((string)($request->post('nazov_discipliny') ?? '')));
        $vykon = strip_tags(trim((string)($request->post('dosiahnuty_vykon') ?? '')));
        $datumRaw = trim((string)($request->post('datum_vykonu') ?? ''));
        $poznamka = strip_tags(trim((string)($request->post('poznamka') ?? '')));

        // Validation
        $formErrors = $this->formErrors($nazov, $vykon, $datumRaw, $poznamka, $isEdit);
        if (count($formErrors) > 0) {
            $record = $isEdit ? Record::getOne($id) : new Record();
            if (!$record) $record = new Record();

            // repopulate fields
            $record->setNazovDiscipliny($nazov);
            $record->setDosiahnutyVykon($vykon ?: null);
            $record->setDatumVykonu($datumRaw ?: null);
            $record->setPoznamka($poznamka ?: null);

            // preserve user_id for edit if missing
            if ($isEdit && $record->getUserId() === 0) {
                $existing = Record::getOne($id);
                if ($existing) $record->setUserId($existing->getUserId());
            }

            return $this->html(['errors' => $formErrors, 'record' => $record], $isEdit ? 'edit' : 'add');
        }

        // Save
        try {
            $identity = $this->user->getIdentity();
            $role = $identity?->getRole() ?? null;
            $userId = $identity?->getId() ?? 0;

            if ($isEdit) {
                $record = Record::getOne($id);
                if (is_null($record)) {
                    throw new \Exception('Záznam neexistuje.');
                }

                if ($role !== 'admin' && $userId !== $record->getUserId()) {
                    throw new HttpException(403, 'Nemáte oprávnenie upravovať tento záznam.');
                }

                $record->setNazovDiscipliny($nazov);
                $record->setDosiahnutyVykon($vykon ?: null);
                $record->setDatumVykonu($datumRaw ?: null);
                $record->setPoznamka($poznamka ?: null);
            } else {
                if ($userId === 0) {
                    throw new \Exception('Prihlásený používateľ nemá platné ID.');
                }
                $record = new Record(null, (int)$userId, $nazov, $vykon ?: null, $datumRaw ?: null, $poznamka ?: null);
            }

            $record->save();
            return $this->redirect($this->url('record.index'));
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $errors[] = 'Nepodarilo sa uložiť záznam: ' . $e->getMessage();

            // Ensure record exists for repopulation
            if (!$record) $record = $isEdit ? Record::getOne($id) : new Record();
            if (!$record) $record = new Record();

            $record->setNazovDiscipliny($nazov);
            $record->setDosiahnutyVykon($vykon ?: null);
            $record->setDatumVykonu($datumRaw ?: null);
            $record->setPoznamka($poznamka ?: null);

            return $this->html(['errors' => $errors, 'record' => $record], $isEdit ? 'edit' : 'add');
        }
    }

    /**
     * Odstráni záznam (len majiteľ alebo admin). Pri AJAX požiadavke vráti JSON.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function delete(Request $request): Response
    {
        // 1. CSRF ochrana (aby niekto iný nemohol poslať link na zmazanie mojho výkonu)
        $this->validateCsrf($request);

        try {
            $id = (int)$request->value('id');
            $record = Record::getOne($id);

            if (is_null($record)) {
                //pre AJAX vratim chybu v JSON formate
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Record nebol nájdený.'], 404);
                }
                throw new HttpException(404);
            }

            // kontrola opravneni
            $identity = $this->user->getIdentity();
            $role = $identity?->getRole() ?? null;
            $userId = $identity?->getId() ?? null;

            if ($role !== 'admin' && $userId !== $record->getUserId()) {
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Nemáte oprávnenie zmazať tento záznam.'], 403);
                }
                throw new HttpException(403, 'Nemáte oprávnenie zmazať tento záznam.');
            }

            $record->delete();

            //AJAX
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }

        } catch (\Exception $e) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()], 500);
            }
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }

        //klasicke presmerovanie ak data-ajax nieje true
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
