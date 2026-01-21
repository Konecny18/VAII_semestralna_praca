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
        const toggles = document.querySelectorAll('.training-active-toggle');

        function getCsrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('#posts-container')?.dataset.csrf || '';
        }

        toggles.forEach(t => {
            t.addEventListener('change', function(){
                const id = this.dataset.id;
                const url = this.dataset.url;
                const checked = this.checked ? 1 : 0;
                const label = document.querySelector('label[for="' + this.id + '"]');
                const csrf = this.dataset.csrf || getCsrf();

                // Optimistically update label
                if (label) label.textContent = checked ? 'Zobrazené' : 'Skryté';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf || '',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id, active: checked })
                }).then(async res => {
                    if (!res.ok) {
                        const txt = await res.text();
                        throw new Error('Server error: ' + res.status + '\n' + txt);
                    }
                    return res.json();
                }).then(data => {
                    if (!data.success) {
                        // revert toggle
                        t.checked = !t.checked;
                        if (label) label.textContent = t.checked ? 'Zobrazené' : 'Skryté';
                        alert(data.message || 'Nepodarilo sa zmeniť viditeľnosť.');
                    } else {
                        if (typeof data.active !== 'undefined' && label) {
                            label.textContent = data.active ? 'Zobrazené' : 'Skryté';
                            t.checked = !!data.active;
                        }
                    }
                }).catch(err => {
                    console.error(err);
                    // revert toggle
                    t.checked = !t.checked;
                    if (label) label.textContent = t.checked ? 'Zobrazené' : 'Skryté';
                    alert('Chyba pri komunikácii so serverom.');
                });
            });
        });
    });
})();
