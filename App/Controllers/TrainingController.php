<?php

namespace App\Controllers;

use App\Models\Training;
use Exception;
use App\Configuration;
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
     */
    public function index(Request $request): Response
    {
        $auth = $this->app->getAuthenticator();
        try {
            $trainings = Training::getAll(null, [], 'den ASC, cas_zaciatku ASC');
            return $this->html([
                'trainings' => $trainings,
                'auth' => $auth
            ]);
        } catch (Exception $e) {
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }
    }

    /**
     * Zobrazí formulár pre vytvorenie nového tréningu (dostupné len pre admina).
     *
     * @param Request $request
     * @return Response HTML stránka s formulárom pre pridanie tréningu.
     * @throws HttpException Ak používateľ nie je autorizovaný.
     */
    public function add(Request $request): Response
    {
        // Only admin can add trainings
        if (!$this->user->isLoggedIn()) {
            return $this->redirect(Configuration::LOGIN_URL);
        }
        $identity = $this->user->getIdentity();
        $role = $identity?->getRole() ?? null;
        if ($role !== 'admin') {
            throw new HttpException(403, 'Nemáte oprávnenie pridávať tréningy.');
        }

        return $this->html([]);
    }

    /**
     * Zobrazí formulár pre úpravu existujúceho tréningu (len admin).
     *
     * @param Request $request
     * @return Response HTML s formulárom na úpravu tréningu.
     * @throws HttpException Ak tréning neexistuje alebo používateľ nie je autorizovaný.
     */
    public function edit(Request $request): Response
    {
        $id = (int)$request->value('id');
        $training = Training::getOne($id);
        if (is_null($training)) {
            throw new HttpException(404);
        }
        // Only admin can edit trainings
        if (!$this->user->isLoggedIn()) {
            return $this->redirect(Configuration::LOGIN_URL);
        }
        $identity = $this->user->getIdentity();
        $role = $identity?->getRole() ?? null;
        if ($role !== 'admin') {
            throw new HttpException(403, 'Nemáte oprávnenie upravovať tréningy.');
        }

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
     */
    public function save(Request $request): Response
    {
        // --- 1. Získanie, Sanitizácia a Normalizácia Vstupu ---
        $id = (int)$request->value('id');
        $isEdit = $id > 0;

        // Získanie a SANITIZÁCIA/Čistenie
        $den = (string)$request->value('den');
        $casStartRaw = trim((string)$request->value('cas_zaciatku'));
        $casEndRaw = trim((string)$request->value('cas_konca'));
        $popis = strip_tags(trim((string)$request->value('popis'))); // KRITICKÉ: SANITIZÁCIA (XSS)

        // Logika normalizácie času (funkcia zostáva lokálna)
        $normalizeTime = function(string $t): ?string {
            if ($t === '') return null;
            // Ak je čas platný a má menej ako 8 znakov (napr. HH:MM), pridaj sekundy
            if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $t)) {
                if (strlen($t) <= 5) {
                    return $t . ':00';
                }
                return $t;
            }
            return null;
        };

        // --- 2. Kontrola Autorizácie (Iba Admin) ---
        if (!$this->user->isLoggedIn() || ($this->user->getIdentity()?->getRole() ?? null) !== 'admin') {
            throw new HttpException(403, 'Nemáte oprávnenie upravovať rozvrh tréningov.');
        }

        // --- 3. Validácia ---
        $formErrors = $this->formErrors($request);

        if (count($formErrors) > 0) {
            // V prípade chyby validácie, re-populujeme Model pre návrat do View
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

        // --- 4. Spracovanie a Uloženie (Iba ak je validácia úspešná) ---
        try {
            // Normalizujeme čas pre uloženie do DB (HH:MM:SS)
            $casStart = $normalizeTime($casStartRaw);
            $casEnd = $normalizeTime($casEndRaw);

            if ($isEdit) {
                $training = Training::getOne($id);
                if (is_null($training)) { throw new HttpException(404); }
            } else {
                $training = new Training();
            }

            // Nastavujeme SANITIZOVANÉ a NORMALIZOVANÉ hodnoty
            $training->setDen($den);
            $training->setCasZaciatku($casStart); // Používame NORMALIZOVANÝ čas
            $training->setCasKonca($casEnd);     // Používame NORMALIZOVANÝ čas
            $training->setPopis($popis);         // Používame SANITIZOVANÝ reťazec (NIKDY null, kvôli NOT NULL v DB)

            $training->save();

            if ($request->isAjax()) {
                return $this->json(['success' => true, 'redirect' => $this->url('training.index')]);
            }
            return $this->redirect($this->url('training.index'));

        } catch (\Throwable $e) {
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
        try {
            $id = (int)$request->value('id');
            $training = Training::getOne($id);

            if (is_null($training)) {
                //pre AJAX vratim chybu v JSON formate
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Training nebol nájdený.'], 404);
                }
                throw new HttpException(404);
            }
            // Only admin can delete trainings
            if (!$this->user->isLoggedIn()) {
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Musíte sa prihlásiť.'], 401);
                }
                return $this->redirect(Configuration::LOGIN_URL);
            }
            $identity = $this->user->getIdentity();
            $role = $identity?->getRole() ?? null;

            if ($role !== 'admin') {
                //kontrola pre AJAX
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Nemáte oprávnenie zmazať tréningy.'], 403);
                }
                throw new HttpException(403, 'Nemáte oprávnenie zmazať tréningy.');
            }

            $training->delete();

            //AJAX uspech
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }

        } catch (Exception $e) {
            //vratenie AJAX chyby
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()], 500);
            }
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
        return $this->redirect($this->url('training.index'));
    }

    private function formErrors(Request $request): array
    {
        $errors = [];
        $maxPopisLength = 100;

        // Získanie hodnôt
        $den = (string)$request->value('den');
        $casZ = trim((string)$request->value('cas_zaciatku'));
        $casK = trim((string)$request->value('cas_konca'));
        $popis = trim((string)$request->value('popis')); // Tu získavame ne-sanitizovaný (surový) popis pre kontrolu dĺžky

        $timeRegex = '/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';
        $validDays = ['Pon','Uto','Str','Stv','Pia','Sob','Ned'];

        // --- 1. Validácia Dňa (ENUM NOT NULL) ---
        if ($den === '' || !in_array($den, $validDays, true)) {
            $errors[] = 'Pole deň musí byť vybrané (Pon..Ned) a mať platnú hodnotu.';
        }

        // --- 2. Validácia Času (TIME NOT NULL) ---
        if ($casZ === '' || !preg_match($timeRegex, $casZ)) {
            $errors[] = 'Pole čas začiatku musí byť v tvare HH:MM alebo HH:MM:SS (24h).';
        }
        if ($casK === '' || !preg_match($timeRegex, $casK)) {
            $errors[] = 'Pole čas konca musí byť v tvare HH:MM alebo HH:MM:SS (24h).';
        }

        // --- 3. Logická Kontrola Času (Začiatok < Koniec) ---
        if (preg_match($timeRegex, $casZ) && preg_match($timeRegex, $casK)) {
            // Prevod na Unix timestamp pre spoľahlivé porovnanie
            $start = strtotime((mb_strlen($casZ) <= 5) ? $casZ . ':00' : $casZ);
            $end = strtotime((mb_strlen($casK) <= 5) ? $casK . ':00' : $casK);

            // Kritická logická kontrola
            if ($start !== false && $end !== false && $start >= $end) {
                $errors[] = 'Čas začiatku musí byť pred časom konca.';
            }
        }

        // --- 4. Validácia Popisu (VARCHAR(100) NOT NULL) ---
        if ($popis === '') {
            $errors[] = 'Pole popis musí byť vyplnené.';
        } elseif (mb_strlen($popis) > $maxPopisLength) {
            $errors[] = 'Pole popis nesmie presiahnuť ' . $maxPopisLength . ' znakov.';
        }

        return $errors;
    }
}
