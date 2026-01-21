/**
 * register-validate-ajax.js
 *
 * Klientská validácia registračného formulára s AJAX kontrolou emailu.
 * - Overuje validitu emailu (regex) a volá server cez GET na kontrolu existencie emailu.
 * - Overuje silu hesla a zhodu potvrdenia hesla.
 * - Zamedzí odoslatiu formulára, ak sú polia nevalidné.
 *
 * Očakávané elementy v DOM:
 * - input#email
 * - input#password
 * - input#password_confirm
 *
 * Poznámky: Skript používa oneskorenie (debounce) pre AJAX volanie.
 */

// Anonymná funkcia (IIFE) – chráni kód, aby sa nebil s inými skriptami
(function registerValidateIife(){
    // Prísny režim - zakazuje chyby ako používanie nedefinovaných premenných
    'use strict';

    // Pomocná funkcia, ktorá odloží vykonanie inej funkcie (fn) o určitý čas (delay)
    function oneskorenie(funkcia, delay) {
        // Premenná, ktorá drží ID aktuálneho časovača
        let t;
        // Vracia novú funkciu, ktorú reálne voláme
        return function(...args) {
            // Ak stlačíš kláves skôr, než uplynie delay, predchádzajúci pokus sa zruší
            clearTimeout(t);
            // Nastaví nový časovač; po uplynutí sa spustí pôvodná funkcia (fn)
            t = setTimeout(() => funkcia.apply(this, args), delay);
        };
    }
    
    document.addEventListener('DOMContentLoaded', function(){
        // --- EMAIL VALIDÁCIA (pôvodná + AJAX) ---
        const emailInput = document.getElementById('email');
        if (emailInput) {
            // Dynamicky vytvoríme <div> pre chybu pod emailom (štandard Bootstrap)
            const emailFeedback = document.createElement('div');
            emailFeedback.className = 'invalid-feedback';
            emailInput.parentNode.appendChild(emailFeedback);

            // Získame URL na kontrolu
            const checkUrl = window.__CHECK_EMAIL_URL__ || '/auth/checkEmail';

            // Definujeme akciu, ktorá sa stane po dopísaní emailu
            const doCheckEmail = oneskorenie(function(){
                // Odstráni medzery na začiatku a konci
                const zadanaHodnota = emailInput.value.trim();
                // Ak je prázdny, vymažeme vizuálne stavy
                if (zadanaHodnota === '') {
                    emailInput.classList.remove('is-invalid','is-valid');
                    return;
                }

                // Regulárny výraz na kontrolu, či text vyzerá ako email (niečo@niečo.niečo)
                const re = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
                if (!re.test(zadanaHodnota)) {
                    emailInput.classList.add('is-invalid');
                    emailFeedback.textContent = 'Zadajte platný email.';
                    return;
                }

                // AJAX VOLANIE: Opýtame sa servera, či email už existuje v DB
                const separator = checkUrl.includes('?') ? '&' : '?';
                fetch(checkUrl + separator + 'email=' + encodeURIComponent(zadanaHodnota))
                    // Odpoveď zmeníme z textu na JSON
                    .then(r => r.json())
                    .then(json => {
                        // Ak PHP vráti {"exists": true}
                        if (json.exists) {
                            emailInput.classList.add('is-invalid');
                            emailFeedback.textContent = 'Email je už registrovaný.';
                            // Ak PHP vráti {"exists": false}
                        } else {
                            emailInput.classList.remove('is-invalid');
                            emailInput.classList.add('is-valid');
                        }
                    });
                // Počkáme 0.4 sekundy po poslednom stlačení klávesy
            }, 400);

            emailInput.addEventListener('input', doCheckEmail);
        }

        // --- VALIDÁCIA HESLA
        const passInput = document.getElementById('password');
        const passConfirmInput = document.getElementById('password_confirm');

        if (passInput) {
            // Vytvoríme miesto pre chybovú správu o sile hesla
            const passFeedback = document.createElement('div');
            passFeedback.className = 'invalid-feedback';
            passInput.parentNode.appendChild(passFeedback);

            const validatePassword = () => {
                const val = passInput.value;
                // RegEx: Aspoň 8 znakov, veľké, malé písmeno, číslo a špeciálny znak
                const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.])[A-Za-z\d@$!%*?&.]{8,}$/;

                if (val === '') {
                    passInput.classList.remove('is-invalid', 'is-valid');
                } else if (!strongRegex.test(val)) {
                    passInput.classList.add('is-invalid');
                    passInput.classList.remove('is-valid');
                    passFeedback.textContent = 'Heslo musí mať aspoň 8 znakov, veľké a malé písmeno, číslo a špeciálny znak (@$!%*?&.).';
                } else {
                    passInput.classList.remove('is-invalid');
                    passInput.classList.add('is-valid');
                }
                // Ak zmením hlavné heslo, automaticky preveríme, či sa stále zhoduje s tým potvrdzovacím
                if (passConfirmInput) validateConfirm();
            };

            // Kontrolujeme silu hesla s oneskorením (funkcia oneskorenie)
            passInput.addEventListener('input', oneskorenie(validatePassword, 400));
        }

        // --- KONTROLA ZHODY HESIEL ---
        if (passConfirmInput) {
            // Miesto pre chybu "Heslá sa nezhodujú"
            const confirmFeedback = document.createElement('div');
            confirmFeedback.className = 'invalid-feedback';
            passConfirmInput.parentNode.appendChild(confirmFeedback);

            const validateConfirm = () => {
                // Ak je pole prázdne, nič nerobíme
                if (passConfirmInput.value === '') {
                    passConfirmInput.classList.remove('is-invalid', 'is-valid');
                    // Ak sa heslá nezhodujú
                } else if (passConfirmInput.value !== passInput.value) {
                    passConfirmInput.classList.add('is-invalid');
                    passConfirmInput.classList.remove('is-valid');
                    confirmFeedback.textContent = 'Heslá sa nezhodujú!';
                } else {
                    passConfirmInput.classList.remove('is-invalid');
                    passConfirmInput.classList.add('is-valid');
                }
            };

            // Tu nemusíme čakať (funkcia oneskorenie), kontrolujeme hneď pri písaní
            passConfirmInput.addEventListener('input', validateConfirm);
        }

        // --- OCHRANA PRED ODOSLANÍM ---
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e){
                // Vyhľadáme všetky prvky, ktoré majú triedu "is-invalid" (majú chybu)
                const invalids = form.querySelectorAll('.is-invalid');
                if (invalids.length > 0) {
                    // ZABLOKUJE odoslanie formulára na server
                    e.preventDefault();
                    // Skočí kurzorom na prvú chybu na stránke
                    invalids[0].focus();
                }
            });
        }

    });
})();