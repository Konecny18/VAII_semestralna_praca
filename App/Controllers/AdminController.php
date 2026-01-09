<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class AdminController
 *
 * Spravuje administratívne akcie v aplikácii. Tento kontrolér poskytuje základnú ochranu prístupu
 * (metóda authorize) a index stránku pre administráciu.
 *
 * @package App\Controllers
 */
class AdminController extends BaseController
{
    /**
     * Overí autorizáciu pre akcie tohto kontroléra.
     *
     * @param Request $request
     * @param string $action Názov akcie, ktorú chceme autorizovať
     * @return bool Vráti true, ak je používateľ prihlásený (má prístup), inak false
     */
    public function authorize(Request $request, string $action): bool
    {
        return $this->user->isLoggedIn();
    }

    /**
     * Zobrazí hlavný admin panel (dashboard).
     *
     * @param Request $request
     * @return Response HTML odpoveď s admin rozhraním
     */
    public function index(Request $request): Response
    {
        return $this->html();
    }
}
