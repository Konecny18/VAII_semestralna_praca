<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\Record;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class RecordController extends BaseController
{
    public function index(Request $request): Response
    {
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

            return $this->html(['records' => $records]);
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
        $formValues = ['nazov_discipliny' => '', 'dosiahnuty_vykon' => '', 'datum_vykovu' => '', 'poznamka' => '', 'id' => null];
        $errors = [];

        if ($request->isPost()) {
            $nazov = trim((string)($request->post('nazov_discipliny') ?? ''));
            $vykon = trim((string)($request->post('dosiahnuty_vykon') ?? ''));
            $datumRaw = trim((string)($request->post('datum_vykonu') ?? ''));
            $poznamka = trim((string)($request->post('poznamka') ?? ''));
            $idRaw = $request->post('id') ?? null;
            $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
            $isEdit = !empty($id);

            $formValues['nazov_discipliny'] = $nazov;
            $formValues['dosiahnuty_vykon'] = $vykon;
            $formValues['datum_vykovu'] = $datumRaw;
            $formValues['poznamka'] = $poznamka;
            $formValues['id'] = $id;

            // validation
            if ($nazov === '') {
                $errors[] = 'Názov disciplíny je povinný.';
            }
            if ($datumRaw !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datumRaw)) {
                $errors[] = 'Dátum má nesprávny formát.';
            }

            if (empty($errors)) {
                try {
                    if ($isEdit) {
                        $record = Record::getOne((int)$id);
                        if (is_null($record)) {
                            throw new \Exception('Záznam neexistuje.');
                        }
                        // Only owner or admin can update
                        $identity = $this->user->getIdentity();
                        $role = $identity?->getRole() ?? null;
                        $userId = $identity?->getId() ?? null;
                        if ($role !== 'admin' && $userId !== $record->getUserId()) {
                            throw new \Exception('Nemáte oprávnenie upravovať tento záznam.');
                        }

                        $record->setNazovDiscipliny($nazov);
                        $record->setDosiahnutyVykon($vykon ?: null);
                        $record->setDatumVykonu($datumRaw ?: null);
                        $record->setPoznamka($poznamka ?: null);
                    } else {
                        // Creating new record: require logged user to avoid DB foreign key errors
                        if (!$this->user->isLoggedIn()) {
                            return $this->redirect(Configuration::LOGIN_URL);
                        }

                        // get current user id from authenticated identity
                        $identity = $this->user->getIdentity();
                        $userId = method_exists($identity, 'getId') ? $identity->getId() : 0;

                        $record = new Record(null, (int)$userId, $nazov, $vykon ?: null, $datumRaw ?: null, $poznamka ?: null);
                    }
                    $record->save();
                    return $this->redirect($this->url('record.index'));
                } catch (\Throwable $e) {
                    $errors[] = 'Nepodarilo sa uložiť záznam: ' . $e->getMessage();
                }
            }
        }

        // prepare model for form
        $record = null;
        if (!empty($formValues['id'])) {
            $existing = Record::getOne((int)$formValues['id']);
            if ($existing !== null) {
                $record = $existing;
                $record->setNazovDiscipliny($formValues['nazov_discipliny']);
                $record->setDosiahnutyVykon($formValues['dosiahnuty_vykon'] ?: null);
                $record->setDatumVykonu($formValues['datum_vykovu'] ?: null);
                $record->setPoznamka($formValues['poznamka'] ?: null);
            }
        }
        if ($record === null) {
            $record = new Record(null, $formValues['id'] ?? 0, $formValues['nazov_discipliny'], $formValues['dosiahnuty_vykon'], $formValues['datum_vykovu'], $formValues['poznamka']);
        }

        return $this->html(array_merge(compact('errors', 'record')),
            empty($formValues['id']) ? 'add' : 'edit');
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
}
