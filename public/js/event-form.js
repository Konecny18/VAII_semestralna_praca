/**
 * event-form.js
 * Validácia formulára podujatí (Plagát, PDF, Dátum).
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const form = document.querySelector('.needs-validation');
    if (!form) return;

    // Konštanty v MB
    const MAX_IMAGE_MB = 2;
    const MAX_PDF_MB = 2;

    const plagat = document.getElementById('plagat');
    const dokument = document.getElementById('dokument_propozicie');
    const datumInput = document.getElementById('datum_podujatia');
    const isEdit = document.querySelector('input[name="id"]')?.value !== '';

    /**
     * Univerzálna funkcia na overenie súboru
     */
    const validateFile = (input, maxSizeMB, allowedExtensions) => {
        if (!input) return true;

        const file = input.files[0];
        const feedback = input.closest('.mb-3')?.querySelector('.invalid-feedback');

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
            return false;
        }

        // 3. Kontrola veľkosti
        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB > maxSizeMB) {
            if (feedback) feedback.textContent = `Súbor je príliš veľký (max ${maxSizeMB} MB).`;
            input.classList.add('is-invalid');
            input.setCustomValidity('size');
            return false;
        }

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

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(inputDate);

        if (selectedDate <= today) {
            if (feedback) feedback.textContent = 'Dátum podujatia musí byť v budúcnosti.';
            datumInput.classList.add('is-invalid');
            datumInput.setCustomValidity('invalid-date');
            return false;
        }

        datumInput.classList.add('is-valid');
        return true;
    };

    // Nastavenie povinného plagátu pri ADD
    if (!isEdit && plagat) {
        plagat.setAttribute('required', 'required');
    }

    // Event listenery pre okamžitú spätnú väzbu
    datumInput?.addEventListener('change', validateDate);
    plagat?.addEventListener('change', () => validateFile(plagat, MAX_IMAGE_MB, ['.jpg', '.jpeg', '.png']));
    dokument?.addEventListener('change', () => validateFile(dokument, MAX_PDF_MB, ['.pdf']));

    // Kontrola pri SUBMITe
    form.addEventListener('submit', function (e) {
        const isPlagatOk = validateFile(plagat, MAX_IMAGE_MB, ['.jpg', '.jpeg', '.png']);
        const isDocOk = validateFile(dokument, MAX_PDF_MB, ['.pdf']);
        const isDateOk = validateDate();

        if (!form.checkValidity() || !isPlagatOk || !isDocOk || !isDateOk) {
            e.preventDefault();
            e.stopPropagation();
        }

        form.classList.add('was-validated');
    }, false);
});