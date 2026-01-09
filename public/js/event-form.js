/**
 * event-form.js
 *
 * Klientská validácia formulára pre podujatia (plagát, PDF propozície, dátum).
 * - Kontroluje príponu a veľkosť súborov (plagát obrázok a PDF dokument) a dátum podujatia (musi byť väčší ako dnes).
 * - Pri odoslaní formu vykoná všetky kontroly a zabráni odoslaniu ak niektorá z nich zlyhá.
 *
 * Použitie:
 * - Skript očakáva, že formulár používa triedu `.needs-validation` a polia majú id `plagat`, `dokument_propozicie`, `datum_podujatia`.
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const form = document.querySelector('.needs-validation');
    if (!form) return;

    var MAX_IMAGE_BYTES = 2 * 1024 * 1024; // 2 MB
    var MAX_PDF_BYTES = 2 * 1024 * 1024; // 2 MB

    // Univerzálna funkcia na overenie súboru
    const validateFile = (input, maxSizeMB, allowedExtensions) => {
        const file = input.files[0];
        // Nájdeme feedback v rámci mb-3 kontajnera
        const feedback = input.closest('.mb-3').querySelector('.invalid-feedback');

        input.classList.remove('is-invalid', 'is-valid');
        input.setCustomValidity('');

        // 1. Ak súbor nie je vybraný
        if (!file) {
            if (input.hasAttribute('required')) {
                input.setCustomValidity('invalid');
                return false;
            }
            return true;
        }

        // 2. Kontrola prípony
        const fileName = file.name.toLowerCase();
        const isExtensionOk = allowedExtensions.some(ext => fileName.endsWith(ext.toLowerCase()));

        if (!isExtensionOk) {
            if (feedback) feedback.textContent = `Povolené formáty: ${allowedExtensions.join(', ')}`;
            input.classList.add('is-invalid');
            input.setCustomValidity('format');
            input.value = '';
            return false;
        }

        // 3. Kontrola veľkosti (použije maxSizeMB poslané pri volaní)
        const fileSizeMB = file.size / 1024 / 1024;
        if (fileSizeMB > maxSizeMB) {
            if (feedback) feedback.textContent = `Súbor je príliš veľký (max ${maxSizeMB} MB).`;
            input.classList.add('is-invalid');
            input.setCustomValidity('size');
            input.value = '';
            return false;
        }

        // Ak je všetko OK
        input.classList.add('is-valid');
        input.setCustomValidity('');
        return true;
    };

    const plagat = document.getElementById('plagat');
    const dokument = document.getElementById('dokument_propozicie');
    const datumInput = document.getElementById('datum_podujatia');
    const isEdit = document.querySelector('input[name="id"]')?.value !== '';

    // Logika pre povinný plagát pri novom podujatí
    if (!isEdit && plagat) {
        plagat.setAttribute('required', 'required');
    }

    // Validate date: must be provided and strictly greater than today
    const getTodayLocalYMD = () => {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    const validateDate = () => {
        if (!datumInput) return true;
        const feedback = datumInput.closest('.mb-3').querySelector('.invalid-feedback');
        datumInput.classList.remove('is-invalid', 'is-valid');
        datumInput.setCustomValidity('');

        const inputDate = datumInput.value;
        if (!inputDate) {
            if (feedback) feedback.textContent = 'Dátum podujatia je povinný.';
            datumInput.classList.add('is-invalid');
            datumInput.setCustomValidity('required');
            return false;
        }

        const today = getTodayLocalYMD();
        if ((inputDate <= today)) {
            if (feedback) feedback.textContent = 'Dátum podujatia musí byť neskôr ako dnešný deň.';
            datumInput.classList.add('is-invalid');
            datumInput.setCustomValidity('invalid-date');
            return false;
        }

        // OK
        datumInput.classList.add('is-valid');
        datumInput.setCustomValidity('');
        if (feedback) feedback.textContent = 'Dátum podujatia je povinný.'; // restore default for future clears
        return true;
    };

    // date change listener
    datumInput?.addEventListener('change', validateDate);

    // --- Listenery pre okamžitú spätnú väzbu ---
    // Plagát: limit 2 MB
    plagat?.addEventListener('change', () => validateFile(plagat, MAX_IMAGE_BYTES / 1024 / 1024, ['.jpg', '.jpeg', '.png']));
    // Dokument: limit 2 MB
    dokument?.addEventListener('change', () => validateFile(dokument, MAX_PDF_BYTES / 1024 / 1024, ['.pdf']));

    // --- Kontrola pri odoslaní formulára ---
    form.addEventListener('submit', function (e) {
        const isPlagatOk = plagat ? validateFile(plagat, MAX_IMAGE_BYTES / 1024 / 1024, ['.jpg', '.jpeg', '.png']) : true;
        const isDocOk = dokument ? validateFile(dokument, MAX_PDF_BYTES / 1024 / 1024, ['.pdf']) : true;
        const isDateOk = validateDate();

        if (!form.checkValidity() || !isPlagatOk || !isDocOk || !isDateOk) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
});