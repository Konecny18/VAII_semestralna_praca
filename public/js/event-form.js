/**
 * event-form.js
 * Validácia formulára podujatí (Plagát, PDF, Dátum).
 * Na strane klienta
 */
// Čaká sa na úplné načítanie HTML dokumentu (DOM)
document.addEventListener('DOMContentLoaded', function () {
    // Zapína prísny režim JS (menej chýb, bezpečnejší kód)
    'use strict';

    // Hľadáme formulár s triedou .needs-validation (štandard Bootstrapu)
    const form = document.querySelector('.needs-validation');
    if (!form) return;

    // Konštanty v MB
    const MAX_IMAGE_MB = 2;
    const MAX_PDF_MB = 2;

    const plagat = document.getElementById('plagat');
    const dokument = document.getElementById('dokument_propozicie');
    const datumInput = document.getElementById('datum_podujatia');
    //zistenie ci je to edit alebo add
    const isEdit = document.querySelector('input[name="id"]')?.value !== '';

    /**
     * Univerzálna funkcia na overenie súboru
     */
    const validateFile = (input, maxSizeMB, allowedExtensions) => {
        // Ak input na stránke neexistuje (napr. v inom formulári), vrátime true,
        // aby sme nezablokovali odosielanie a predišli pádu skriptu na chybe "null".
        //ked to neexistuje tak to nema co validovat takze je to ok
        if (!input) return true;

        // Získame prvý vybraný súbor z inputu
        const file = input.files[0];
        // Nájdeme element pre chybovú hlášku, ktorý je v rovnakom bloku (mb-3)
        const feedback = input.closest('.mb-3')?.querySelector('.invalid-feedback');

        // Resetujeme vizuálne stavy (rámčeky) a internú validáciu prehliadača
        input.classList.remove('is-invalid', 'is-valid');
        input.setCustomValidity('');

        // KONTROLA 1: Ak používateľ nevybral žiaden súbor
        if (!file) {
            // Ak má input atribút required (povinné), vrátime chybu
            if (input.hasAttribute('required')) {
                input.setCustomValidity('invalid');
                return false;
            }
            // Ak nie je povinný a nič nie je vybrané, je to v poriadku
            return true;
        }

        // KONTROLA 2: Kontrola typu (prípony) súboru
        // Názov súboru na malé písmená
        const fileName = file.name.toLowerCase();
        // Skontrolujeme, či končí niektorou z povolených koncoviek (.jpg, .pdf...)
        const isExtensionOk = allowedExtensions.some(ext => fileName.endsWith(ext.toLowerCase()));

        if (!isExtensionOk) {
            // Ak je zlá prípona, prepíšeme text v chybe a označíme input na červeno
            if (feedback) feedback.textContent = `Povolené formáty: ${allowedExtensions.join(', ')}`;
            input.classList.add('is-invalid');
            // povie prehliadaču: "toto neodosielaj"
            input.setCustomValidity('format');
            return false;
        }

        // KONTROLA 3: Kontrola veľkosti (file.size je v bajtoch, delíme miliónom pre MB)
        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB > maxSizeMB) {
            if (feedback) feedback.textContent = `Súbor je príliš veľký (max ${maxSizeMB} MB).`;
            input.classList.add('is-invalid');
            input.setCustomValidity('size');
            return false;
        }

        // Ak prešlo všetkým, pridáme zelený rámček
        input.classList.add('is-valid');
        return true;
    };

    /**
     * Validácia dátumu (musí byť > dnes)
     */
    const validateDate = () => {
        if (!datumInput) return true;

        const feedback = datumInput.closest('.mb-3')?.querySelector('.invalid-feedback');
        datumInput.classList.remove('is-invalid', 'is-valid');
        datumInput.setCustomValidity('');

        const inputDate = datumInput.value;
        if (!inputDate) {
            datumInput.classList.add('is-invalid');
            datumInput.setCustomValidity('required');
            return false;
        }

        // Vytvoríme objekt dnešného dátumu a vynulujeme čas (zaujímajú nás len dni)
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(inputDate);

        // LOGIKA: Dátum nesmie byť menší alebo rovný dnešku (chceme budúce podujatia)
        if (selectedDate <= today) {
            if (feedback) feedback.textContent = 'Dátum podujatia musí byť v budúcnosti.';
            datumInput.classList.add('is-invalid');
            datumInput.setCustomValidity('invalid-date');
            return false;
        }

        datumInput.classList.add('is-valid');
        return true;
    };

    // ŠPECIÁLNA LOGIKA: Pri pridávaní (ADD) musí byť plagát povinný.
    // Pri editácii ho nenastavujeme ako required, lebo tam už asi starý je.
    if (!isEdit && plagat) {
        plagat.setAttribute('required', 'required');
    }

    // "Živá" validácia: Akonáhle používateľ niečo zmení, okamžite mu ukážeme, či je to dobre
    datumInput?.addEventListener('change', validateDate);
    plagat?.addEventListener('change', () => validateFile(plagat, MAX_IMAGE_MB, ['.jpg', '.jpeg', '.png']));
    dokument?.addEventListener('change', () => validateFile(dokument, MAX_PDF_MB, ['.pdf']));

    // FINÁLNA KONTROLA: Pri pokuse o odoslanie formulára
    form.addEventListener('submit', function (e) {
        const isPlagatOk = validateFile(plagat, MAX_IMAGE_MB, ['.jpg', '.jpeg', '.png']);
        const isDocOk = validateFile(dokument, MAX_PDF_MB, ['.pdf']);
        const isDateOk = validateDate();

        // form.checkValidity() overuje natívne HTML5 pravidlá (ako email, prázdne polia)
        // Ak hocičo z toho zlyhalo, zastavíme odosielanie (preventDefault)
        if (!form.checkValidity() || !isPlagatOk || !isDocOk || !isDateOk) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Bootstrap trieda, ktorá aktivuje vizuálne hlášky pod všetkými poliami
        form.classList.add('was-validated');
    }, false);
});