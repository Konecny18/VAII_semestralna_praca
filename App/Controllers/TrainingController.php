<?php

namespace App\Controllers;

use App\Models\Training;
use Exception;
use App\Configuration;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class TrainingController extends BaseController
{
    public function index(Request $request): Response
    {
        try {
            $trainings = Training::getAll(null, [], 'den ASC, cas_zaciatku ASC');
            return $this->html(['trainings' => $trainings]);
        } catch (Exception $e) {
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }
    }

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

    public function save(Request $request): Response
    {
        $id = (int)$request->value('id');

        if ($id > 0) {
            $training = Training::getOne($id);
            if (is_null($training)) {
                throw new HttpException(404);
            }
        } else {
            $training = new Training();
        }

        // Normalize and set fields
        $den = (string)$request->value('den');
        $casStartRaw = trim((string)$request->value('cas_zaciatku'));
        $casEndRaw = trim((string)$request->value('cas_konca'));
        $popis = trim((string)$request->value('popis'));

        // Normalize time inputs: allow HH:MM or HH:MM:SS, convert to HH:MM:SS
        $normalizeTime = function(string $t): ?string {
            if ($t === '') return null;
            // Accept H:MM, HH:MM or HH:MM:SS
            if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $t)) {
                if (strlen($t) <= 5) {
                    return $t . ':00';
                }
                return $t;
            }
            return null;
        };

        $casStart = $normalizeTime($casStartRaw);
        $casEnd = $normalizeTime($casEndRaw);

        // Only admin can save (create/update) trainings
        if (!$this->user->isLoggedIn()) {
            return $this->redirect(Configuration::LOGIN_URL);
        }
        $identity = $this->user->getIdentity();
        $role = $identity?->getRole() ?? null;
        if ($role !== 'admin') {
            throw new HttpException(403, 'Nemáte oprávnenie upravovať rozvrh tréningov.');
        }

        $training->setDen($den);
        $training->setCasZaciatku($casStart);
        $training->setCasKonca($casEnd);
        $training->setPopis($popis === '' ? null : $popis);

        $formErrors = $this->formErrors($request);
        if (count($formErrors) > 0) {
            return $this->html(['training' => $training, 'formErrors' => $formErrors], ($id > 0) ? 'edit' : 'add');
        }

        try {
            $training->save();
        } catch (Exception $e) {
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }

        return $this->redirect($this->url('training.index'));
    }

    public function delete(Request $request): Response
    {
        try {
            $id = (int)$request->value('id');
            $training = Training::getOne($id);
            if (is_null($training)) {
                throw new HttpException(404);
            }
            // Only admin can delete trainings
            if (!$this->user->isLoggedIn()) {
                return $this->redirect(Configuration::LOGIN_URL);
            }
            $identity = $this->user->getIdentity();
            $role = $identity?->getRole() ?? null;
            if ($role !== 'admin') {
                throw new HttpException(403, 'Nemáte oprávnenie zmazať tréningy.');
            }

            $training->delete();
        } catch (Exception $e) {
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
        return $this->redirect($this->url('training.index'));
    }

    private function formErrors(Request $request): array
    {
        $errors = [];
        $den = (string)$request->value('den');
        $validDays = ['Pon','Uto','Str','Stv','Pia','Sob','Ned'];
        if ($den === '' || !in_array($den, $validDays, true)) {
            $errors[] = 'Pole deň musí byť vybrané (Pon..Ned).';
        }

        $casZ = trim((string)$request->value('cas_zaciatku'));
        $casK = trim((string)$request->value('cas_konca'));
        $timeRegex = '/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';
        if ($casZ === '' || !preg_match($timeRegex, $casZ)) {
            $errors[] = 'Pole čas začiatku musí byť v tvare HH:MM alebo HH:MM:SS (24h).';
        }
        if ($casK === '' || !preg_match($timeRegex, $casK)) {
            $errors[] = 'Pole čas konca musí byť v tvare HH:MM alebo HH:MM:SS (24h).';
        }

        // Optional: ensure start < end if both valid
        if (preg_match($timeRegex, $casZ) && preg_match($timeRegex, $casK)) {
            $start = strtotime((strlen($casZ) <=5) ? $casZ . ':00' : $casZ);
            $end = strtotime((strlen($casK) <=5) ? $casK . ':00' : $casK);
            if ($start !== false && $end !== false && $start >= $end) {
                $errors[] = 'Čas začiatku musí byť pred časom konca.';
            }
        }

        if (trim((string)$request->value('popis')) === '') {
            $errors[] = 'Pole popis musí byť vyplnené.';
        }

        return $errors;
    }
}
