<?php

namespace App\Controllers;

use App\Models\Training;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class TrainingController
 *
 * Spravuje CRUD operácie nad rozvrhom tréningov. Tento kontrolér umožňuje prehliadať zoznam tréningov,
 * pridávať, upravovať a mazať záznamy o tréningoch. Všetky akcie na úpravu rozvrhu sú dostupné len pre adminov.
 *
 * @package App\Controllers
 */
class TrainingController extends BaseController
{
    /**
     * Zobrazí zoznam všetkých tréningov zoradených podľa dňa a času.
     *
     * @param Request $request HTTP request objekt (na získanie kontextu/užívateľa)
     * @return Response Vráti HTML odpoveď s vykresleným zoznamom tréningov.
     * @throws HttpException
     */
    public function index(Request $request): Response
    {
        // 1. ZÍSKANIE AUTENTIFIKÁTORA
        // Potrebujeme ho vo View, aby sme rozhodli, či zobraziť admin tlačidlá (Pridať/Upraviť tréning)
        $auth = $this->app->getAuthenticator();
        try {
            /* --- 2. NAČÍTANIE A ZORAĎOVANIE --- */
            // getAll(podmienka, parametre, zoradenie)
            // Zoradenie je kľúčové:
            // Najprv podľa dňa (pondelok -> nedeľa)
            // a následne podľa času začiatku (skoršie tréningy budú vyššie).
            $trainings = Training::getAll(null, [], 'den ASC, cas_zaciatku ASC');

            // 3. ODOSLANIE DÁT DO ŠABLÓNY
            return $this->html([
                'trainings' => $trainings,
                'auth' => $auth
            ]);
        } catch (Exception $e) {
            // V prípade technického zlyhania databázy vyhodíme systémovú chybu 500
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }
    }

    /**
     * Zobrazí formulár pre vytvorenie nového tréningu (dostupné len pre admina).
     *
     * @return Response HTML stránka s formulárom pre pridanie tréningu.
     * @throws HttpException Ak používateľ nie je autorizovaný.
     */
    public function add(): Response
    {
        // Kontrola oprávnení - len admin môže pridávať tréningy
        $this->checkAdmin();
        // 2. ZOBRAZENIE FORMULÁRA
        // Metóda html() bez parametrov automaticky vyhľadá šablónu
        // prislúchajúcu tomuto controlleru a akcii
        return $this->html();
    }

    /**
     * Zobrazí formulár pre úpravu existujúceho tréningu (len admin).
     *
     * @param Request $request
     * @return Response HTML s formulárom na úpravu tréningu.
     * @throws HttpException Ak tréning neexistuje alebo používateľ nie je autorizovaný.
     * @throws Exception
     */
    public function edit(Request $request): Response
    {
        // Kontrola oprávnení - len admin môže upravovať tréningy
        $this->checkAdmin();

        // 2. ZÍSKANIE ID Z POŽIADAVKY
        // ID tréningu získame z URL (napr. training/edit?id=5).
        $id = (int)$request->value('id');

        // 3. NAČÍTANIE ZÁZNAMU Z DATABÁZY
        // Pomocou statickej metódy modelu Training sa pokúsime nájsť záznam v DB.
        $training = Training::getOne($id);

        // 4. OŠETRENIE CHYBOVÉHO STAVU
        // Ak tréning s daným ID neexistuje (napr. niekto ho už zmazal alebo prepísal URL),
        // vyhodíme 404 - Nenájdené. Tým predídeme pádu aplikácie na null pointer exception.
        if (is_null($training)) {
            throw new HttpException(404);
        }

        // 5. VRÁTENIE ŠABLÓNY S DÁTAMI
        // Objekt tréningu pošleme do view, aby sa formulár mohol predvyplniť pôvodnými údajmi.
        return $this->html(['training' => $training]);
    }

    /**
     * Spracuje uloženie/aktualizáciu tréningu. Prijíma POST dáta z formulára, validuje ich a uloží do DB.
     *
     * - Pri neúspešnej validácii vráti zobrazenie formulára s chybami.
     * - Pri úspechu presmeruje na index tréningov.
     *
     * @param Request $request HTTP request obsahujúci POST údaje
     * @return Response Redirect alebo JSON pri AJAX požiadavke
     * @throws HttpException pri nedostatočnej autorizácii alebo iných závažných chybách
     * @throws Exception
     */
    public function save(Request $request): Response
    {
        // 1. CSRF
        $this->validateCsrf($request);

        // 2. AUTORIZÁCIA
        $this->checkAdmin();

        // 2. IDENTIFIKÁCIA: Zistíme, či záznam už existuje (Edit) alebo vytvárame nový (Add)
        $id = (int)$request->value('id');
        $isEdit = $id > 0;

        // --- 3. SANITIZÁCIA (Čistenie vstupov) ---
        $den = (string)$request->value('den');
        $casStartRaw = trim((string)$request->value('cas_zaciatku'));
        $casEndRaw = trim((string)$request->value('cas_konca'));
        // strip_tags: Kľúčové pre bezpečnosť (XSS). Zabráni adminovi (alebo hackerovi)
        // vložiť do popisu tréningu škodlivý skript, ktorý by sa spustil ostatným používateľom.
        $popis = strip_tags(trim((string)$request->value('popis'))); // KRITICKÉ: SANITIZÁCIA (XSS)

        // 4. NORMALIZÁCIA ČASU (Pomocná funkcia)
        // SQL typ 'TIME' vyžaduje formát HH:MM:SS. Prehliadače často posielajú len HH:MM.
        // Táto funkcia zabezpečí, že dáta budú pre DB vždy v správnom formáte.
        $normalizeTime = function(string $t): ?string {
            if ($t === '') return null;
            // Ak je čas platný a má menej ako 8 znakov (napr. HH:MM), pridaj sekundy
            if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $t)) {
                if (strlen($t) <= 5) {
                    return $t . ':00'; // Pridá sekundy :00
                }
                return $t;
            }
            return null;
        };

        // --- 5. VALIDÁCIA ---
        $formErrors = $this->formErrors($request);

        if (count($formErrors) > 0) {
            // UX: Ak je chyba, vrátime používateľa k formuláru a predvyplníme ho tým, čo už napísal (Repopulácia)
            $training = $isEdit ? Training::getOne($id) : new Training();

            // Nastavíme SANITIZOVANÉ a vyčistené hodnoty pre zobrazenie
            $training->setDen($den);
            // Pri poliach typu 'time' vraciame surovú hodnotu (HH:MM) pre správne zobrazenie
            $training->setCasZaciatku($casStartRaw);
            $training->setCasKonca($casEndRaw);
            $training->setPopis($popis); // Nastavujeme vyčistený reťazec (NIKDY NULL)

            if ($request->isAjax()) {
                return $this->json(['success' => false, 'errors' => $formErrors]);
            }
            return $this->html(['training' => $training, 'formErrors' => $formErrors], $isEdit ? 'edit' : 'add');
        }

        // --- 6. UKLADANIE (Try-Catch blok) ---
        try {
            // Pred uložením premeníme surové časy na normalizované (HH:MM:SS)
            $casStart = $normalizeTime($casStartRaw);
            $casEnd = $normalizeTime($casEndRaw);

            if ($isEdit) {
                $training = Training::getOne($id);
                if (is_null($training)) { throw new HttpException(404); }
            } else {
                $training = new Training();
            }

            // Naplnenie modelu vyčistenými dátami
            $training->setDen($den);
            $training->setCasZaciatku($casStart); // Používame NORMALIZOVANÝ čas
            $training->setCasKonca($casEnd);     // Používame NORMALIZOVANÝ čas
            $training->setPopis($popis);         // Používame SANITIZOVANÝ reťazec (NIKDY null, kvôli NOT NULL v DB)

            $training->save();

            // AJAX podpora: Ak sa ukladá cez JS, vrátime JSON s inštrukciou na presmerovanie
            if ($request->isAjax()) {
                return $this->json(['success' => true, 'redirect' => $this->url('training.index')]);
            }
            return $this->redirect($this->url('training.index'));

        } catch (\Throwable $e) {
            // Globálna záchranná sieť pre chyby databázy
            $message = 'DB chyba: ' . $e->getMessage();

            // Pri DB chybe
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'errors' => [$message]]);
            }
            throw new HttpException(500, $message);
        }
    }

    /**
     * Odstráni tréning so zadaným ID. Ak je požiadavka AJAX, vráti JSON úspech/chybu.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException ak tréning neexistuje alebo používateľ nemá oprávnenie
     * @throws Exception pri inom selhaní
     */
    public function delete(Request $request): Response
    {
        // 1. CSRF Ochrana
        $this->validateCsrf($request);

        // 2. AUTORIZÁCIA
        $this->checkAdmin();

        try {
            // 3. IDENTIFIKÁCIA ZÁZNAMU
            $id = (int)$request->value('id');
            $training = Training::getOne($id);

            // 4. OŠETRENIE NEEXISTENCIE (Defenzívne programovanie)
            if (is_null($training)) {
                // Ak sa maže cez AJAX (napr. tlačidlo v tabuľke), vrátime JSON
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Training nebol nájdený.']);
                }
                // Pri klasickom volaní URL vyhodíme 404
                throw new HttpException(404);
            }

            // 5. FYZICKÉ ZMAZANIE Z DB
            // Metóda delete() na objekte Training vykoná SQL: DELETE FROM trainings WHERE id = ...
            $training->delete();

            // 6. ÚSPEŠNÁ ODPOVEĎ PRE AJAX
            // Informujeme frontend, že riadok môže zmiznúť z UI
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }

        } catch (Exception $e) {
            // 7. OŠETRENIE VÝNIMIEK (napr. chyba pripojenia, cudzie kľúče)
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()]);
            }
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
        // 8. REDIRECT (Fallback)
        // Ak sa nepoužil AJAX, presmerujeme používateľa späť na zoznam tréningov
        return $this->redirect($this->url('training.index'));
    }

    private function formErrors(Request $request): array
    {
        $errors = [];
        // Zodpovedá limitu VARCHAR(100) v databáze
        $maxPopisLength = 100;

        // --- 1. ZÍSKANIE HODNÔT ---
        $den = (string)$request->value('den');
        $casZ = trim((string)$request->value('cas_zaciatku'));
        $casK = trim((string)$request->value('cas_konca'));
        $popis = trim((string)$request->value('popis')); // Tu získavame ne-sanitizovaný (surový) popis pre kontrolu dĺžky

        // Regulárny výraz pre 24-hodinový formát času (HH:MM alebo HH:MM:SS)
        $timeRegex = '/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';
        // Povolené hodnoty pre dni (musia sedieť s tvojím výberom vo formulári a ENUM v DB)
        $validDays = ['Pon','Uto','Str','Stv','Pia','Sob','Ned'];

        // --- 2. VALIDÁCIA DŇA ---
        // Kontrolujeme nielen či nie je prázdny, ale aj či patrí do povoleného zoznamu (Whitelisting)
        if ($den === '' || !in_array($den, $validDays, true)) {
            $errors[] = 'Pole deň musí byť vybrané (Pon..Ned) a mať platnú hodnotu.';
        }

        // --- 3. VALIDÁCIA FORMÁTU ČASU ---
        if ($casZ === '' || !preg_match($timeRegex, $casZ)) {
            $errors[] = 'Pole čas začiatku musí byť v tvare HH:MM alebo HH:MM:SS (24h).';
        }
        if ($casK === '' || !preg_match($timeRegex, $casK)) {
            $errors[] = 'Pole čas konca musí byť v tvare HH:MM alebo HH:MM:SS (24h).';
        }

        // --- 4. LOGICKÁ KONTROLA (Časová os) ---
        // Ak oba časy prešli základným formátom, skontrolujeme ich logiku
        if (preg_match($timeRegex, $casZ) && preg_match($timeRegex, $casK)) {
            // Prevod na timestamp, aby sme mohli časy porovnať ako čísla
            // Dopĺňame :00, ak používateľ zadal len HH:MM, aby strtotime fungoval spoľahlivo
            $start = strtotime((mb_strlen($casZ) <= 5) ? $casZ . ':00' : $casZ);
            $end = strtotime((mb_strlen($casK) <= 5) ? $casK . ':00' : $casK);

            // Zabezpečíme, aby tréning netrval záporný čas alebo nula minút
            if ($start !== false && $end !== false && $start >= $end) {
                $errors[] = 'Čas začiatku musí byť pred časom konca.';
            }
        }

        // --- 5. VALIDÁCIA POPISU ---
        if ($popis === '') {
            $errors[] = 'Pole popis musí byť vyplnené.';
        } elseif (mb_strlen($popis) > $maxPopisLength) {
            $errors[] = 'Pole popis nesmie presiahnuť ' . $maxPopisLength . ' znakov.';
        }

        return $errors;
    }
}
