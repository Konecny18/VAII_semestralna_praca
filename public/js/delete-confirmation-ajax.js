/**
 * delete-confirmation-ajax.js
 *
 * Skript pre potvrdenie vymazania položiek. Podporuje klasickú (form submit / navigácia) aj AJAX verziu.
 * Používa SweetAlert2 na zobrazenie modalného potvrdenia.
 *
 * Očakávané atribúty na tlačidle / elemente, ktorý spúšťa vymazanie:
 * - data-message       : (voliteľné) text, ktorý sa zobrazí v potvrdení
 * - data-form-id       : (voliteľné) id formulára, ktorý sa má odoslať pri klasickom spracovaní
 * - data-ajax="true"  : (voliteľné) ak je prítomné a nastavené na 'true', použije sa AJAX (fetch)
 * - data-target-id     : (voliteľné) id DOM elementu, ktorý sa má odstrániť z DOM po úspešnom AJAX vymazaní
 * - href               : (voliteľné) URL ktorá sa použije pre AJAX alebo pre klasickú navigáciu, ak nie je dostupný parent <form>
 *
 * Očakávaná odpoveď servera pri AJAX požiadavke (JSON):
 * {
 *   success: true|false,
 *   message?: 'chybovy alebo success text'
 * }
 *
 * Poznámky a bezpečnosť:
 * - Tento skript iba odošle požiadavku; overovanie práv (autorizácia, CSRF) a samotné vymazanie musí spraviť server.
 * - Pre AJAX požiadavky odporúčam posielať CSRF token v hlavičkách alebo v tele požiadavky.
 * - Pri chybách sieťového pripojenia alebo parsovania JSON sa používateľovi zobrazí chybové hlásenie.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Vyberieme všetky tlačidlá / elementy, ktoré môžu spustiť zmazanie
    const deleteElements = document.querySelectorAll('.delete-btn, .btn-delete-event');

    /**
     * Pre každý element pripojíme click listener, ktorý zobrazí potvrdenie a následne
     * vykoná buď klasické odoslanie formulára alebo AJAX požiadavku podľa atribútu data-ajax.
     */
    deleteElements.forEach(element => {
        element.addEventListener('click', function (event) {
            // Zabránime default akcii (link / button) a bublaniu eventu
            event.preventDefault();
            event.stopPropagation();

            // Získame základné informácie z atributov elementu
            const message = this.getAttribute('data-message') || "Naozaj zmazať?";
            const formId = this.getAttribute('data-form-id');
            const parentForm = this.closest('form');
            const linkUrl = this.getAttribute('href');

            // Ak chceme AJAX, očakávame data-ajax="true"
            const isAjax = this.getAttribute('data-ajax') === 'true';
            // ID elementu, ktorý sa má odstrániť z DOM po úspechu (napr. riadok tabuľky)
            const targetId = this.getAttribute('data-target-id');

            // Použijeme SweetAlert2 modal pre potvrdenie akcie
            Swal.fire({
                title: 'Si si istý?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Áno, zmazať!',
                cancelButtonText: 'Zrušiť'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (isAjax) {
                        // --- AJAX spracovanie ---
                        // URL z atributu href alebo ak existuje parent form, použijeme jeho action.
                        const url = linkUrl || (parentForm ? parentForm.action : '');

                        // Send fetch POST request. Poznámka: ak potrebujete CSRF, pridajte ho do headers tu.
                        fetch(url, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(response => {
                                // Pokúsime sa parsovať JSON; ak server nevráti JSON, prejdeme do catch
                                return response.json();
                            })
                            .then(data => {
                                // Očakávame objekt { success: boolean, message?: string }
                                if (data && data.success) {
                                    // Ak je zadané targetId, odstránime element z DOM bez reloadu
                                    if (targetId) {
                                        const targetEl = document.getElementById(targetId);
                                        if (targetEl) targetEl.remove();
                                    }
                                    Swal.fire('Vymazané!', 'Položka bola odstránená.', 'success');
                                } else {
                                    // Server vrátil success=false alebo nejakú chybovú správu
                                    Swal.fire('Chyba!', (data && data.message) ? data.message : 'Nepodarilo sa vymazať.', 'error');
                                }
                            })
                            .catch(error => {
                                // Chyba pri sieti alebo pri spracovaní odpovede
                                console.error('AJAX Error:', error);
                                Swal.fire('Chyba!', 'Chyba spojenia so serverom.', 'error');
                            });

                    } else {
                        // --- Klasické spracovanie (formular / redirect) ---
                        // Priorita: explicitné formId -> parent form -> href link
                        if (formId) {
                            const f = document.getElementById(formId);
                            if (f) f.submit();
                        } else if (parentForm) {
                            parentForm.submit();
                        } else if (linkUrl) {
                            // Ak nemáme formu, presmerujeme pre potvrdenie cez GET/POST endpoint
                            window.location.href = linkUrl;
                        }
                    }
                }
            });
        });
    });
});