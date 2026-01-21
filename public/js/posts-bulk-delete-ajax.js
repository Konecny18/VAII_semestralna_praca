/**
 * posts-bulk-delete-ajax.js
 *
 * Skript pre hromadné mazanie príspevkov (posts) pomocou zaškrtávacích políčok a jedného AJAX volania.
 * - Očakáva:
 *    - checkboxy s triedou `.post-checkbox` a value = ID príspevku
 *    - tlačidlo #btn-bulk-delete-posts s data-url pre endpoint a voliteľným data-csrf tokenom
 *    - voliteľný checkbox #select-all-posts na označenie všetkých
 *    - voliteľný #selected-count element na zobrazenie počtu vybraných
 * - Posiela POST (FormData) s parametrom `ids[]` pre každý ID a hlavičkou `X-CSRF-TOKEN`.
 * - Pri úspechu odstráni karty z DOM a zobrazí potvrdzovací dialóg (SweetAlert ak je dostupný).
 */

document.addEventListener('DOMContentLoaded', function () {
    // Checkbox v hlavičke tabuľky na označenie všetkého
    const selectAll = document.getElementById('select-all-posts');
    // Funkcia, ktorá vždy vráti čerstvé pole všetkých checkboxov položiek
    const checkboxes = () => Array.from(document.querySelectorAll('.post-checkbox'));
    // Tlačidlo, ktoré spustí hromadné mazanie
    const bulkBtn = document.getElementById('btn-bulk-delete-posts');
    // Element (číslo), ktorý ukazuje, koľko položiek je vybratých
    const vybranePolozky = document.getElementById('selected-count');

    // Hľadá CSRF token na viacerých miestach (v datasete tlačidla, v meta tagu alebo v kontajneri)
    function getCsrfFromButton() {
        if (!bulkBtn) return null;
        return bulkBtn.dataset.csrf ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            document.querySelector('#posts-container')?.dataset.csrf || null;
    }

    // Funkcia, ktorá prepína viditeľnosť tlačidla "Vymazať vybrané"
    function updateBulkUI() {
        const checkedCount = document.querySelectorAll('.post-checkbox:checked').length;
        // Aktualizuje číslo vybratých
        if (vybranePolozky) vybranePolozky.textContent = checkedCount;
        // Ak je vybraté viac ako 0, tlačidlo sa zobrazí, inak sa skryje
        if (bulkBtn) bulkBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
    }

    function handleDelete(ids) {
        // Ak na stránke nie je tlačidlo na mazanie, funkcia nepokračuje.
        if (!bulkBtn) return;

        // Vytiahneme URL adresu z data-atribútu tlačidla (napr. /post/bulk-delete)
        const url = bulkBtn.dataset.url;
        // Získame CSRF token
        const csrf = getCsrfFromButton();

        // Poistka: Ak chýba URL, vypíšeme chybu (programátorská chyba v HTML)
        if (!url) {
            alert('Bulk delete URL not configured.');
            return;
        }

        const doFetch = () => {
            // FormData simuluje klasický formulár, PHP to spracuje ako $_POST['ids']
            const form = new FormData();

            // Pridá každé ID do poľa ktore sme dostali v parametry
            ids.forEach(id => form.append('ids[]', id));
            // Include CSRF token as header (server reads HTTP_X_CSRF_TOKEN)
            fetch(url, {
                // Používame metódu POST, lebo meníme/mažeme dáta
                method: 'POST',
                headers: {
                    // Hovoríme serveru, že ide o AJAX
                    'X-Requested-With': 'XMLHttpRequest',
                    // Posielame bezpečnostný token v hlavičke
                    'X-CSRF-TOKEN': csrf || ''
                },
                // "Virtuálny formulár" s našimi IDčkami posielame ako telo požiadavky
                body: form
            }).then(async response => {

                // Ak server vráti chybu (404, 500...), vypíšeme text chyby
                if (!response.ok) {
                    // Prečítame si, akú chybu server vypísal
                    const text = await response.text();
                    throw new Error('Server error: ' + response.status + '\n' + text);
                }
                // Ak je všetko OK, skúsime zmeniť text na JSON objekt
                return response.json();
            }).then(data => {
                // Server poslal {"success": true}
                if (data.success) {
                    // Pre každé ID, ktoré sme poslali, nájdeme na stránke kartu s fotkou
                    ids.forEach(id => {
                        const el = document.getElementById('post-card-' + id);
                        // Fyzicky odstránime fotku z obrazovky (DOM)
                        if (el) el.remove();
                    });
                    // Prepočítame počet vybraných kusov (teraz bude 0) a skryjeme tlačidlo
                    updateBulkUI();

                    // ukazem potvrdenie o zmazani
                    if (window.Swal) {
                        Swal.fire('Hotovo', data.message || 'Vymazané', 'success');
                    } else {
                        alert(data.message || 'Vymazané');
                    }
                } else {
                    // Server poslal {"success": false}, napr. "Nemáte oprávnenie"
                    if (window.Swal) {
                        Swal.fire('Chyba', data.message || 'Nepodarilo sa vymazať vybrané.', 'error');
                    } else {
                        alert(data.message || 'Nepodarilo sa vymazať vybrané.');
                    }
                }
            }).catch(err => {
                // Sem skočím, ak vypadol internet, server poslal HTML chybu, alebo JS v .then padol
                console.error(err);
                if (window.Swal) {
                    Swal.fire('Chyba', err.message || 'Nepodarilo sa komunikovať so serverom.', 'error');
                } else {
                    alert('Chyba: ' + (err.message || 'Nepodarilo sa komunikovať so serverom.'));
                }
            });
        };

        // Zobrazenie potvrdenia (SweetAlert alebo klasický confirm)
        if (window.Swal) {
            Swal.fire({
                title: 'Hromadné mazanie',
                text: `Naozaj chceš vymazať ${ids.length} vybraných fotiek?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Áno, zmazať!'
            }).then((result) => {
                // AŽ TERAZ: Ak používateľ v okne naozaj klikol na "Áno, zmazať!"
                // Spustíme to hneď definované odosielanie
                if (result.isConfirmed) doFetch();
            });
        } else {
            // Fallback pre prípad, že SweetAlert knižnica nie je načítaná (klasické okno v prehliadači)
            if (confirm(`Naozaj chceš vymazať ${ids.length} vybraných fotiek?`)) doFetch();
        }
    }

    // Funkcia, ktorá naviaže 'change' event na každý checkbox
    function bindCheckboxes(){
        checkboxes().forEach(cb => {
            if (!cb.dataset.bound) {
                // dataset.bound zabezpečí, aby sme na jeden checkbox nenaviazali event viackrát
                cb.addEventListener('change', updateBulkUI);
                cb.dataset.bound = '1';
            }
        });
    }

    // Kliknutie na "Vybrať všetko" v hlavičke
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes().forEach(cb => cb.checked = this.checked);
            updateBulkUI();
        });
    }

    // Kliknutie na hlavné tlačidlo hromadného mazania
    if (bulkBtn) {
        bulkBtn.addEventListener('click', function () {
            // Vytvoríme pole IDčiek len z tých checkboxov, ktoré sú zaškrtnuté
            const ids = checkboxes().filter(cb => cb.checked).map(cb => cb.value);
            if (ids.length === 0) return;
            handleDelete(ids);
        });
    }

    // Prvotné spustenie pri načítaní stránky
    bindCheckboxes();
    updateBulkUI();

    // If posts can be loaded dynamically later, you can re-run bindCheckboxes() after insertion
});