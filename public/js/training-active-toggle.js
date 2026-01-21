/**
 * training-active-toggle.js
 *
 * Skript pre admin inline toggle (zobraziť/skryť) tréningov.
 * - Očakáva checkbox elementy s triedou `.training-active-toggle` a data-atribútmi:
 *     - data-id  : id záznamu
 *     - data-url : URL na endpoint, ktorý spracuje zmenu (POST)
 *     - optional data-csrf : CSRF token, ak nie je meta tag
 * - Pri zmene stavu odošle POST pomocou fetch s JSON telom {id, active} a hlavičkami
 *   `X-Requested-With` a `X-CSRF-TOKEN`.
 * - Optimisticky aktualizuje label (text) a pri chybe zmeny ho vráti späť.
 */

(function(){
    'use strict';

    document.addEventListener('DOMContentLoaded', function(){
        // Vyberie všetky prepínače (checkboxy), ktoré majú túto triedu
        const toggles = document.querySelectorAll('.training-active-toggle');

        // Pomocná funkcia na získanie CSRF tokenu z rôznych miest
        function getCsrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('#posts-container')?.dataset.csrf
                || '';
        }

        toggles.forEach(t => {
            t.addEventListener('change', function(){
                // Získanie údajov z data-atribútov priamo z daného prepínača
                const id = this.dataset.id;
                const url = this.dataset.url;
                const checked = this.checked ? 1 : 0;
                const label = document.querySelector('label[for="' + this.id + '"]');
                const csrf = this.dataset.csrf || getCsrf();

                fetch(url, {
                    // Používame POST, pretože meníme dáta v DB
                    method: 'POST',
                    headers: {
                        // Označenie pre PHP, že ide o AJAX
                        'X-Requested-With': 'XMLHttpRequest',
                        // Bezpečnostný token
                        'X-CSRF-TOKEN': csrf || '',
                        // Hovoríme serveru, že posielame JSON
                        'Content-Type': 'application/json'
                    },
                    // Dáta zabalené do reťazca
                    body: JSON.stringify({ id: id, active: checked })
                }).then(async res => {
                    // Ak server vráti chybu (napr. 500 alebo 403)
                    if (!res.ok) {
                        const txt = await res.text();
                        throw new Error('Server error: ' + res.status + '\n' + txt);
                    }
                    return res.json();
                }).then(data => {
                    if (!data.success) {
                        // REVERT: Ak server zahlási chybu (napr. nie si Admin),
                        // musíme prepínač vrátiť do pôvodného stavu
                        t.checked = !t.checked;
                        if (label) label.textContent = t.checked ? 'Zobrazené' : 'Skryté';
                        alert(data.message || 'Nepodarilo sa zmeniť viditeľnosť.');
                    } else {
                        // Ak je všetko v poriadku, potvrdíme stav podľa toho, čo poslal server
                        if (typeof data.active !== 'undefined' && label) {
                            label.textContent = data.active ? 'Zobrazené' : 'Skryté';
                            t.checked = !!data.active;
                        }
                    }
                }).catch(err => {
                    // Ak nastane chyba siete (napr. výpadok internetu)
                    console.error(err);
                    // Vrátenie prepínača späť
                    t.checked = !t.checked;
                    if (label) label.textContent = t.checked ? 'Zobrazené' : 'Skryté';
                    alert('Chyba pri komunikácii so serverom.');
                });
            });
        });
    });
})();
