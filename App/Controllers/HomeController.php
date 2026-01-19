<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class HomeController
 * Handles actions related to the home page and other public actions.
 *
 * This controller includes actions that are accessible to all users, including a default landing page and a contact
 * page. It provides a mechanism for authorizing actions based on user permissions.
 *
 * @package App\Controllers
 */
class HomeController extends BaseController
{
    /**
     * Authorizes controller actions based on the specified action name.
     *
     * In this implementation, all actions are authorized unconditionally.
     *
     * @param string $action The action name to authorize.
     * @return bool Returns true, allowing all actions.
     */
    public function authorize(Request $request, string $action): bool
    {
        // Tu vraciame vždy 'true', pretože domovská stránka, kontakt aj info o klube
        // sú verejne dostupné pre každého návštevníka (hostia aj prihlásení).
        return true;
    }

    /**
     * Displays the default home page.
     *
     * This action serves the main HTML view of the home page.
     *
     * @return Response The response object containing the rendered HTML for the home page.
     */
    public function index(Request $request): Response
    {
        // Metóda html() automaticky hľadá súbor home/index.view.php
        return $this->html();
    }

    /**
     * Displays the contact page.
     *
     * This action serves the HTML view for the contact page, which is accessible to all users without any
     * authorization.
     *
     * @return Response The response object containing the rendered HTML for the contact page.
     */
    public function contact(): Response
    {
        // Hľadá súbor home/contact.view.php
        return $this->html();
    }

    /**
     * Zobrazí stránku s informáciami o klube.
     *
     * @return Response
     */
    public function klub(): Response
    {
        return $this->html();
    }

    /**
     * Zobrazí stránku s partnermi / sponzormi.
     *
     * @return Response
     */
    public function sponzor(): Response
    {
        return$this->html();
    }
}
