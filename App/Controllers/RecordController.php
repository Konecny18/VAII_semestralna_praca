<?php

namespace App\Controllers;

use App\Models\Record;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Session;

class RecordController extends BaseController
{
    public function index(Request $request): Response
    {
        try {
            return $this->html([
                'records' => Record::getAll(null, [], 'id DESC')
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
    }

    public function add(Request $request): Response
    {
        return $this->html();
    }

    public function edit(Request $request): Response
    {
        $id = (int)$request->value('id');
        $record = Record::getOne($id);
        if (is_null($record)) {
            throw new HttpException(404);
        }
        return $this->html(compact('record'), 'edit');
    }

    public function save(Request $request): Response
    {
        $formValues = ['nazov_discipliny' => '', 'dosiahnuty_vykon' => '', 'datum_vykonu' => '', 'poznamka' => '', 'id' => null];
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
            $formValues['datum_vykonu'] = $datumRaw;
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
                        $record->setNazovDiscipliny($nazov);
                        $record->setDosiahnutyVykon($vykon ?: null);
                        $record->setDatumVykonu($datumRaw ?: null);
                        $record->setPoznamka($poznamka ?: null);
                    } else {
                        // get current user id from session
                        $session = new Session();
                        $userId = $session->get('user_id', 0) ?? 0;
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
                $record->setDatumVykonu($formValues['datum_vykonu'] ?: null);
                $record->setPoznamka($formValues['poznamka'] ?: null);
            }
        }
        if ($record === null) {
            $record = new Record(null, $formValues['id'] ?? 0, $formValues['nazov_discipliny'], $formValues['dosiahnuty_vykon'], $formValues['datum_vykonu'], $formValues['poznamka']);
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
            $record->delete();
        } catch (\Exception $e) {
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }

        return $this->redirect($this->url('record.index'));
    }
}
