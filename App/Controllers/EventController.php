<?php

namespace App\Controllers;

use App\Models\Event;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\UploadedFile;
use Framework\Http\HttpException;
use Framework\Http\Responses\Response;

class EventController extends BaseController
{
    public function index(Request $request): Response
    {
        try {
            $events = Event::getAll(null, [], 'datum_podujatia DESC');
            return $this->html(compact('events'));
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
        $event = Event::getOne($id);
        if (is_null($event)) {
            throw new HttpException(404);
        }
        return $this->html(compact('event'), 'edit');
    }

    public function save(Request $request): Response
    {
        $formValues = ['nazov' => '', 'plagat' => '', 'popis' => '', 'link_prihlasovanie' => '', 'dokument_propozicie' => '', 'datum_podujatia' => '', 'id' => null];
        $errors = [];

        if ($request->isPost()) {
            $nazov = strip_tags(trim((string)($request->post('nazov') ?? '')));
            $idRaw = $request->post('id') ?? null;
            $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
            $isEdit = !empty($id);

            $formValues['nazov'] = $nazov;
            $formValues['id'] = $id;
            $formValues['popis'] = strip_tags(trim((string)($request->post('popis') ?? '')));
            $formValues['link_prihlasovanie'] = trim((string)($request->post('link_prihlasovanie') ?? ''));
            $formValues['datum_podujatia'] = trim((string)($request->post('datum_podujatia') ?? ''));

            // Basic validation
            if ($nazov === '') {
                $errors[] = 'Názov podujatia je povinný.';
            }

            // Handle file uploads (plagat = poster image, dokument_propozicie = PDF)
            $newPlagatFull = null;
            $oldPlagatFull = null;
            $newDocFull = null;
            $oldDocFull = null;

            try {
                // load existing if editing
                $existing = null;
                if ($isEdit) {
                    $existing = Event::getOne((int)$id);
                }

                $plagatFile = $request->file('plagat');
                $docFile = $request->file('dokument_propozicie');

                // images dir
                // project root is two levels up from App/Controllers -> use dirname(__DIR__, 2)
                $publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
                $uploadsDir = $publicDir . 'uploads' . DIRECTORY_SEPARATOR;
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0755, true);
                }

                // Handle poster image
                if ($plagatFile instanceof UploadedFile && $plagatFile->isOk() && $plagatFile->getName() !== '') {
                    $allowedImg = ['image/jpeg', 'image/png'];
                    if (!in_array($plagatFile->getType(), $allowedImg)) {
                        $errors[] = 'Plagát musí byť JPG alebo PNG.';
                    } elseif ($plagatFile->getSize() > 5 * 1024 * 1024) {
                        $errors[] = 'Plagát nesmie byť väčší ako 5 MB.';
                    } else {
                        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $plagatFile->getName());
                        $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe;
                        $dest = $uploadsDir . $filename;
                        if ($plagatFile->store($dest)) {
                            $formValues['plagat'] = 'uploads/' . $filename;
                            $newPlagatFull = $dest;
                            if ($isEdit && $existing && $existing->getPlagat() != '') {
                                $oldPlagatFull = $publicDir . str_replace('/', DIRECTORY_SEPARATOR, $existing->getPlagat());
                            }
                        } else {
                            $errors[] = 'Nepodarilo sa uložiť plagát.';
                        }
                    }
                } else {
                    if ($isEdit) {
                        $existing = $existing ?? Event::getOne((int)$id);
                        if ($existing) {
                            $formValues['plagat'] = $existing->getPlagat();
                        }
                    }
                }

                // Handle document PDF
                if ($docFile instanceof UploadedFile && $docFile->isOk() && $docFile->getName() !== '') {
                    if ($docFile->getType() !== 'application/pdf') {
                        $errors[] = 'Dokument musí byť PDF.';
                    } elseif ($docFile->getSize() > 10 * 1024 * 1024) {
                        $errors[] = 'Dokument nesmie byť väčší ako 10 MB.';
                    } else {
                        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $docFile->getName());
                        $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe;
                        $dest = $uploadsDir . $filename;
                        if ($docFile->store($dest)) {
                            $formValues['dokument_propozicie'] = 'uploads/' . $filename;
                            $newDocFull = $dest;
                            if ($isEdit && $existing && $existing->getDokumentPropozicie() != '') {
                                $oldDocFull = $publicDir . str_replace('/', DIRECTORY_SEPARATOR, $existing->getDokumentPropozicie());
                            }
                        } else {
                            $errors[] = 'Nepodarilo sa uložiť dokument.';
                        }
                    }
                } else {
                    if ($isEdit) {
                        $existing = $existing ?? Event::getOne((int)$id);
                        if ($existing) {
                            $formValues['dokument_propozicie'] = $existing->getDokumentPropozicie();
                        }
                    }
                }

            } catch (\Throwable $t) {
                $errors[] = 'Chyba pri nahrávaní súborov: ' . $t->getMessage();
            }

            if (empty($errors)) {
                try {
                    if ($isEdit) {
                        $event = Event::getOne((int)$id);
                        if (is_null($event)) {
                            throw new \Exception('Podujatie neexistuje.');
                        }
                        $event->setNazov($formValues['nazov']);
                        $event->setPopis($formValues['popis']);
                        $event->setLinkPrihlasovanie($formValues['link_prihlasovanie']);
                        $event->setDatumPodujatia($formValues['datum_podujatia']);
                        $event->setPlagat($formValues['plagat']);
                        $event->setDokumentPropozicie($formValues['dokument_propozicie']);
                    } else {
                        $event = new Event(null,
                            $formValues['nazov'],
                            $formValues['plagat'] ?? '',
                            $formValues['popis'] ?? '',
                            $formValues['link_prihlasovanie'] ?? '',
                            $formValues['dokument_propozicie'] ?? '',
                            $formValues['datum_podujatia'] ?? null
                        );
                    }
                    $event->save();

                    // delete old files if replaced
                    if ($newPlagatFull !== null && $oldPlagatFull !== null && file_exists($oldPlagatFull)) {
                        @unlink($oldPlagatFull);
                    }
                    if ($newDocFull !== null && $oldDocFull !== null && file_exists($oldDocFull)) {
                        @unlink($oldDocFull);
                    }

                    return $this->redirect($this->url('event.index'));
                } catch (\Throwable $e) {
                    // cleanup newly uploaded files on failure
                    if (isset($newPlagatFull) && file_exists($newPlagatFull)) {
                        @unlink($newPlagatFull);
                    }
                    if (isset($newDocFull) && file_exists($newDocFull)) {
                        @unlink($newDocFull);
                    }
                    $errors[] = 'Nepodarilo sa uložiť podujatie: ' . $e->getMessage();
                }
            }

        }

        // show form with errors
        $event = null;
        if (!empty($formValues['id'])) {
            $existingForForm = Event::getOne((int)$formValues['id']);
            if ($existingForForm !== null) {
                $event = $existingForForm;
                if (isset($formValues['nazov'])) $event->setNazov($formValues['nazov']);
                if (isset($formValues['plagat'])) $event->setPlagat($formValues['plagat']);
                if (isset($formValues['popis'])) $event->setPopis($formValues['popis']);
                if (isset($formValues['link_prihlasovanie'])) $event->setLinkPrihlasovanie($formValues['link_prihlasovanie']);
                if (isset($formValues['dokument_propozicie'])) $event->setDokumentPropozicie($formValues['dokument_propozicie']);
                if (isset($formValues['datum_podujatia'])) $event->setDatumPodujatia($formValues['datum_podujatia']);
            }
        }
        if ($event === null) {
            $event = new Event();
        }

        return $this->html(array_merge(compact('errors', 'event')),
            (empty($formValues['id']) ? 'add' : 'edit'));
    }

    public function delete(Request $request): Response
    {
        try {
            $id = (int)$request->value('id');
            $event = Event::getOne($id);
            if (is_null($event)) {
                throw new HttpException(404);
            }

            // remove files if exist
            $publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
             if ($event->getPlagat()) {
                 $file = $publicDir . str_replace('/', DIRECTORY_SEPARATOR, $event->getPlagat());
                 if (file_exists($file)) @unlink($file);
             }
             if ($event->getDokumentPropozicie()) {
                 $file = $publicDir . str_replace('/', DIRECTORY_SEPARATOR, $event->getDokumentPropozicie());
                 if (file_exists($file)) @unlink($file);
             }

             $event->delete();
         } catch (\Exception $e) {
             throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
         }

         return $this->redirect($this->url('event.index'));
     }
 }
