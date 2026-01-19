<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\Record;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\DB\Connection;
use PDO;
use Throwable;

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
     * @throws HttpException
     */
    public function index(Request $request): Response
    {
        $auth = $this->app->getAuthenticator();
        try {
            // 1. KONTROLA PRIHLÁSENIA
            // Recordy sú citlivé údaje, preto sem nepustíme nikoho, kto nie je prihlásený.
            $appUser = $this->user ?? null;
            if (!$appUser || !method_exists($appUser, 'isLoggedIn') || !$appUser->isLoggedIn()) {
                // Not logged in -> redirect to log in
                return $this->redirect(Configuration::LOGIN_URL);
            }

            // 2. IDENTIFIKÁCIA POUŽÍVATEĽA A JEHO ROLY
            $identity = $appUser->getIdentity();
            if ($identity === null) {
                return $this->redirect(Configuration::LOGIN_URL);
            }

            $role = method_exists($identity, 'getRole') ? $identity->getRole() : null;

            // 3. FILTROVANIE DÁT PODĽA OPRÁVNENÍ
            if ($role === 'admin' || $role === 'trener') {
                // Admin a tréner majú "božský režim" – vidia záznamy úplne všetkých
                $records = Record::getAll(null, [], 'id DESC');
            } else {
                // Bežný používateľ vidí len to, čo sám vytvoril (podľa user_id)
                $userId = method_exists($identity, 'getId') ? $identity->getId() : null;
                if ($userId === null) {
                    return $this->redirect(Configuration::LOGIN_URL);
                }
                $records = Record::getAll('user_id = :uid', [':uid' => $userId], 'id DESC');
            }

            // 4. OPTIMALIZOVANÉ NAČÍTANIE MIEN VLASTNÍKOV (N+1 Problém)
            // Namiesto toho, aby sme sa pri každom zázname pýtali DB na meno používateľa,
            // nazbierame si všetky unikátne ID používateľov...
            $owners = [];
            $userIds = [];
            foreach ($records as $r) {
                $uid = $r->getUserId();
                if ($uid !== null) $userIds[$uid] = $uid;
            }
            if (!empty($userIds)) {
                try {
                    $conn = Connection::getInstance();
                    // Dynamicky vytvoríme reťazec s otazníkmi (napr. "?,?,?") podľa počtu ID
                    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                    //vytiahne z databazy udaje
                    $sql = "SELECT id, meno, priezvisko FROM users WHERE id IN ($placeholders)";
                    //pockaj na data najskor sa poslu tie otazniky (kvoli bezpecnosti)
                    $stmt = $conn->prepare($sql);
                    //poslu sa skutocne id namiesto tych otaznikov
                    $stmt->execute(array_values($userIds));
                    //stiahne vsetky vysledky z tabulky users
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $row) {
                        // Do pomocného pola uložíme celé meno pod kľúčom ID používateľa
                        $owners[(int)$row['id']] = $row['meno'] . ' ' . $row['priezvisko'];
                    }
                } catch (Throwable) {
                    // on DB error, leave owners empty — view will fall back to user id
                    $owners = [];
                }
            }

            // 5. ZOBRAZENIE
            return $this->html([
                'records' => $records,
                'owners' => $owners,
                'auth' => $auth
            ]);
        } catch (Exception $e) {
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
    }

    /**
     * Zobrazí formulár pre pridanie nového záznamu. Vyžaduje prihlásenie.
     *
     * @return Response
     */
    public function add(): Response
    {
        // 1. KONTROLA AUTENTIFIKÁCIE
        // Na rozdiel od predošlých controllerov, kde sme kontrolovali rolu admina cez checkAdmin(),
        // tu nám stačí, aby bol používateľ prihlásený (isLoggedIn).
        // Každý člen klubu (atlet, tréner, admin) má právo pridať si vlastný záznam.
        if (!$this->user->isLoggedIn()) {
            // Ak nie je prihlásený, pošleme ho na prihlasovaciu stránku definovanú v konfigurácii.
            return $this->redirect(Configuration::LOGIN_URL);
        }

        // 2. ZOBRAZENIE FORMULÁRA
        // html() vráti šablónu record/add.view.php, kde používateľ vyplní údaje o svojom výkone.
        return $this->html();
    }

    /**
     * Zobrazí formulár na úpravu záznamu (len majiteľ alebo admin).
     *
     * @param Request $request
     * @return Response
     * @throws HttpException ak záznam neexistuje alebo nie je oprávnenie
     * @throws Exception
     */
    public function edit(Request $request): Response
    {
        // 1. ZÍSKANIE ZÁZNAMU
        $id = (int)$request->value('id');
        $record = Record::getOne($id);

        // 2. KONTROLA EXISTENCIE
        // Ak záznam v DB neexistuje, vrátime chybu 404.
        if (is_null($record)) {
            throw new HttpException(404);
        }

        // 3. KONTROLA VLASTNÍCTVA (Autorizácia)
        // Získame identitu prihláseného používateľa
        $identity = $this->user->getIdentity();
        $role = $identity?->getRole() ?? null;
        $userId = $identity?->getId() ?? null;

        /* LOGIKA: Záznam môže upraviť iba:
           a) Administrátor (má rolu 'admin')
           b) Majiteľ záznamu (jeho ID sa zhoduje s user_id v zázname)
        */
        if ($role !== 'admin' && $userId !== $record->getUserId()) {
            throw new HttpException(403, 'Nemáte oprávnenie upravovať tento záznam.');
        }

        // 4. ZOBRAZENIE
        return $this->html(compact('record'), 'edit');
    }

    /**
     * Uloží nový alebo upravený záznam. Vykonáva server-side validáciu vstupov.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws Exception
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

        // 2. INICIALIZÁCIA A IDENTIFIKÁCIA OPERÁCIE
        // edit alebo add
        $idRaw = $request->post('id') ?? null;
        $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
        $isEdit = !empty($id);

        // 3. SANITIZÁCIA (Ochrana pred XSS)
        // strip_tags() odstráni HTML značky, trim() odstráni prebytočné medzery
        $nazov = strip_tags(trim((string)($request->post('nazov_discipliny') ?? '')));
        $vykon = strip_tags(trim((string)($request->post('dosiahnuty_vykon') ?? '')));
        $datumRaw = trim((string)($request->post('datum_vykonu') ?? ''));
        $poznamka = strip_tags(trim((string)($request->post('poznamka') ?? '')));

        // 4. VALIDÁCIA
        $formErrors = $this->formErrors($nazov, $vykon, $datumRaw, $poznamka);
        if (count($formErrors) > 0) {
            // Ak sú chyby, musíme používateľovi vrátiť to, čo už napísal, aby to nemusel ťukať znova.
            $record = $isEdit ? Record::getOne($id) : new Record();
            if (!$record) $record = new Record();

            // "Nasilu" do objektu natlačíme dáta z POSTu, aby sa zobrazili vo formulári.
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

        // --- 5. SAMOTNÝ ZÁPIS DO DATABÁZY ---
        try {
            $identity = $this->user->getIdentity();
            $role = $identity?->getRole() ?? null;
            $userId = $identity?->getId() ?? 0;

            if ($isEdit) {
                // --- SCENÁR: EDITÁCIA ---
                $record = Record::getOne($id);
                if (is_null($record)) {
                    throw new Exception('Záznam neexistuje.');
                }

                // AUTORIZÁCIA: Ak nie som admin a ID záznamu mi nepatrí, vyhodím chybu 403.
                // Toto bráni tomu, aby si používateľ zmenil ID v inspectore a upravil výkon niekoho iného.
                if ($role !== 'admin' && $userId !== $record->getUserId()) {
                    throw new HttpException(403, 'Nemáte oprávnenie upravovať tento záznam.');
                }

                $record->setNazovDiscipliny($nazov);
                $record->setDosiahnutyVykon($vykon ?: null);
                $record->setDatumVykonu($datumRaw ?: null);
                $record->setPoznamka($poznamka ?: null);
            } else {
                // --- SCENÁR: NOVÝ ZÁZNAM ---
                if ($userId === 0) {
                    throw new Exception('Prihlásený používateľ nemá platné ID.');
                }
                // Pri novom zázname natvrdo priradíme ID aktuálne prihláseného (bezpečné).
                $record = new Record(null, (int)$userId, $nazov, $vykon ?: null, $datumRaw ?: null, $poznamka ?: null);
            }

            // save() vykoná SQL INSERT alebo UPDATE podľa toho, či má objekt pridelené ID.
            $record->save();
            return $this->redirect($this->url('record.index'));

        } catch (HttpException $e) {
            // CATCH 1: Tieto chyby (napr. 403) chceme nechať "prebublať" do frameworku
            // aby sa zobrazila správna chybová stránka (napr. Prístup zamietnutý).
            throw $e;
        } catch (Throwable $e) {
            // CATCH 2: Záchranná sieť pre nečakané chyby (napr. pád DB)
            $errors[] = 'Nepodarilo sa uložiť záznam: ' . $e->getMessage();

            // Aj pri systémovej chybe sa snažíme vrátiť používateľa do formulára a zachovať jeho dáta
            if (!$record) $record = $isEdit ? Record::getOne($id) : new Record();
            if (!$record) $record = new Record();

            $record->setNazovDiscipliny($nazov);
            $record->setDosiahnutyVykon($vykon ?: null);
            $record->setDatumVykonu($datumRaw ?: null);
            $record->setPoznamka($poznamka ?: null);

            // Vrátime HTML s chybovou hláškou namiesto "bielej obrazovky"
            return $this->html(['errors' => $errors, 'record' => $record], $isEdit ? 'edit' : 'add');
        }
    }

    /**
     * Odstráni záznam (len majiteľ alebo admin). Pri AJAX požiadavke vráti JSON.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws Exception
     */
    public function delete(Request $request): Response
    {
        // 1. CSRF ochrana (aby niekto iný nemohol poslať link na zmazanie mojho výkonu)
        $this->validateCsrf($request);

        try {
            // 2. ZÍSKANIE A KONTROLA EXISTENCIE
            $id = (int)$request->value('id');
            $record = Record::getOne($id);

            if (is_null($record)) {
                // Ak ide o AJAX požiadavku (bez reloadu stránky), vrátime chybu v JSON
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Record nebol nájdený.']);
                }
                // Pri klasickom kliknutí na link vyhodíme 404
                throw new HttpException(404);
            }

            // 3. KONTROLA OPRÁVNENÍ (Autorizácia na úrovni riadku)
            $identity = $this->user->getIdentity();
            $role = $identity?->getRole() ?? null;
            $userId = $identity?->getId() ?? null;

            // Logika: Zmazať môže buď ADMIN, alebo VLASTNÍK záznamu.
            if ($role !== 'admin' && $userId !== $record->getUserId()) {
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Nemáte oprávnenie zmazať tento záznam.']);
                }
                throw new HttpException(403, 'Nemáte oprávnenie zmazať tento záznam.');
            }

            // 4. SAMOTNÉ ZMAZANIE
            $record->delete();

            // 5. ODPOVEĎ PRE AJAX
            // Ak JavaScript na frontende čaká na výsledok, pošleme mu informáciu o úspechu.
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }

        } catch (Exception $e) {
            // 6. OŠETRENIE CHÝB (napr. chyba spojenia s DB)
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()]);
            }
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }

        // 7. KLASICKÉ PRESMEROVANIE
        // Ak nebol použitý AJAX (napr. staršie prehliadače alebo vypnutý JS),
        // po zmazaní sa stránka jednoducho reloadne späť na zoznam.
        return $this->redirect($this->url('record.index'));
    }

    private function formErrors(string $nazov, string $vykon, string $datumRaw, string $poznamka): array
    {
        $errors = [];
        // Limit definovaný v štruktúre DB (VARCHAR(255))
        $maxTextLength = 255;
        $minNazovLength = 2;

        // --- 1. Názov disciplíny (VARCHAR(255) NOT NULL) ---
        if ($nazov === '') {
            $errors[] = 'Názov disciplíny je povinný.';
        } elseif (mb_strlen($nazov) < $minNazovLength) {
            // Použitie mb_strlen zabezpečí správne počítanie dĺžky aj pri slovenskej diakritike (UTF-8)
            $errors[] = 'Názov disciplíny musí mať aspoň ' . $minNazovLength . ' znaky.';
        } elseif (mb_strlen($nazov) > $maxTextLength) {
            $errors[] = 'Názov disciplíny nesmie presiahnuť ' . $maxTextLength . ' znakov.';
        }

        // --- 2. Dátum výkonu (TIMESTAMP NOT NULL) ---
        if ($datumRaw === '') {
            $errors[] = 'Dátum výkonu je povinný.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datumRaw)) {
            // Regulárny výraz overí, či reťazec vyzerá ako dátum (napr. 2024-12-24)
            $errors[] = 'Dátum má nesprávny formát (očakáva sa YYYY-MM-DD).';
        } else {
            $timestamp = strtotime($datumRaw);
            $today = strtotime(date('Y-m-d'));

            if (!$timestamp) {
                $errors[] = 'Zadaný dátum je neplatný.';
            } elseif ($timestamp > $today) {
                // Logické pravidlo: športovec nemôže zapísať výkon, ktorý ešte len dosiahne
                $errors[] = 'Dátum výkonu nemôže byť v budúcnosti.';
            }
        }

        // --- 3. DOSIAHNUTÝ VÝKON (Voliteľné pole) ---
        // Keďže v DB je DEFAULT NULL, kontrolujeme dĺžku, len ak používateľ niečo napísal
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
