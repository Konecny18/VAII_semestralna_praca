/**
 * register-validate.js
 *
 * Klientská (frontend) validácia a asynchrónna kontrola dostupnosti emailu pri registrácii.
 * - Debounced AJAX volanie na endpoint /auth/checkEmail (alebo URL poskytnutú vo view cez window.__CHECK_EMAIL_URL__).
 * - Pridáva vizuálnu spätnú väzbu (Bootstrap triedy is-valid / is-invalid a element .invalid-feedback).
 *
 * Použitie:
 * - Umiestnite tento skript na stránku s formulárom registrácie, kde input pre email má id="email".
 * - Voliteľne definujte global `window.__CHECK_EMAIL_URL__` s vlastnou URL.
 *
 * Bezpečnosť a UX:
 * - Skript najprv validuje formát emailu na klientovi a len potom osloví server.
 * - Volania sú debounced (pauza 400 ms), aby sa znížil počet požiadaviek pri písaní.
 */

// Client-side email availability check for registration form
// - Debounced AJAX call to /auth/checkEmail (or url generated in the view)
(function(){
    'use strict';

    /*posiela poziadavku na kontrolu email az po istom case ked pouzivatel uz nepise
    * keby tu nieje tak po kazdom tuknuti do klavestnice by sa posielala poziadavka na kontrolu*/
    function debounce(fn, delay) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    /*pocka kym sa nacita cela struktura HTML a potom spusti kod*/
    document.addEventListener('DOMContentLoaded', function(){
        /*najde policko s emailom a vytvori div do ktoreho bude vypisovat chyby*/
        const emailInput = document.getElementById('email');
        if (!emailInput) return;
        const feedback = document.createElement('div');
        //nastavuje bootstrap triedu pre div
        feedback.className = 'invalid-feedback';
        //vlozenie spravy do HTML struktury
        emailInput.parentNode.appendChild(feedback);

        const checkUrl = window.__CHECK_EMAIL_URL__ || '/auth/checkEmail';

        /*pomocne funkcie pre vizual*/
        const setInvalid = (msg) => {
            emailInput.classList.add('is-invalid');
            emailInput.classList.remove('is-valid');
            feedback.textContent = msg;
        };
        const setValid = (msg) => {
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
            feedback.textContent = msg || '';
        };

        const doCheck = debounce(function(){
            //upravi email napr pokial su tam medzy na zaciatku
            const val = emailInput.value.trim();
            //pokial pouzivatel vymaze vsetko z policka tak toto vrati do povodneho stavu bez errorov a potvrdenia
            if (val === '') {
                emailInput.classList.remove('is-invalid','is-valid');
                feedback.textContent = '';
                return;
            }
            // kontorluje ci email obsahuje @ a . ak nie tak napise rovno chybu a neotravuje server
            const re = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
            if (!re.test(val)) {
                setInvalid('Zadajte platný email.');
                return;
            }

            /*keby tu nieje tak v adrese su dva otazniky a web nevie co s tym tak keby zadam taky isty email co je zaregistrovany tak ma to hodi do catch
            * ale vdaka tomu separatoru tak mi to vycisti adresu tak ze adresa splna standarty tak server to vie precitat*/
            const separator = checkUrl.includes('?') ? '&' : '?';
            fetch(checkUrl + separator + 'email=' + encodeURIComponent(val), { credentials: 'same-origin' })
                .then(r => r.json())
                .then(json => {
                    if (!json.success) {
                        setInvalid(json.message || 'Chyba pri overovaní emailu.');
                        return;
                    }
                    if (json.exists) {
                        setInvalid('Email je už registrovaný.');
                    } else {
                        setValid('Email je voľný.');
                    }
                }).catch(() => {
                    setInvalid('Chyba pri overovaní emailu.');
                });
        }, 400);

        //spusta sa po pauze debounce
        emailInput.addEventListener('input', doCheck);
        //spusta sa ked pouzivatel klikne na dalsie policko
        emailInput.addEventListener('blur', doCheck);

        // nedovoli odoslat formular ked je email neplatny
        const form = emailInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e){
                if (emailInput.classList.contains('is-invalid')) {
                    e.preventDefault();
                    e.stopPropagation();
                    emailInput.focus();
                }
            });
        }
    });
})();
