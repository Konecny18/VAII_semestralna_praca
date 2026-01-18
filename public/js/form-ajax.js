/**
 * form-ajax.js
 *
 * Univerzálny frontend helper na odosielanie formulárov cez AJAX (fetch).
 * - Očakáva atribút `data-ajax-form` na formulári a voliteľny prvok s `data-ajax-feedback` pre zobrazenie správ.
 * - Automaticky pridáva spinner do tlačidiel pri odosielaní a spracuje JSON odpoveď servera
 *   v tvare { success: boolean, message?: string, redirect?: string, errors?: [] }.
 *
 * Použitie:
 * - Pridajte atribut `data-ajax-form` do form elementu.
 * - Vo vnútri formulára pridajte element (napr. <div>) s `data-ajax-feedback` pre chybové / úspešné správy.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Find CSRF token from meta (if present)
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

    // Grab every form that opted into the AJAX helper through the data attribute.
    const ajaxForms = document.querySelectorAll('form[data-ajax-form]');

    // Repaint the feedback area so the user sees success/error info.
    const createFeedbackMessage = (element, type, messages) => {
        if (!element) {
            return;
        }
        const lines = Array.isArray(messages) ? messages : [messages];
        element.innerHTML = lines.map((line) => `· ${line}`).join('<br>');
        // ensure bootstrap alert class is present
        element.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info', 'alert');
        element.classList.add('alert', 'alert-' + (type === 'success' ? 'success' : type === 'info' ? 'info' : 'danger'));
        element.style.display = 'block';
    };

    ajaxForms.forEach((form) => {
        // Track the feedback element inside the current form (if present).
        const feedbackEl = form.querySelector('[data-ajax-feedback]');
        // Collect submit buttons so we can disable them during sending.
        const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        // Remember each button's original label so we can restore it later.
        const buttonStates = new Map(submitButtons.map((button) => [button, button.innerHTML]));
        const setLoadingState = (isLoading) => {
            submitButtons.forEach((button) => {
                button.disabled = isLoading;
                if (isLoading) {
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + buttonStates.get(button);
                } else {
                    button.innerHTML = buttonStates.get(button);
                }
            });
        };

        form.addEventListener('submit', async (event) => {
            // Stop the default synchronous submission.
            event.preventDefault();
            // Hide previous messages and show a sending status.
            if (feedbackEl) feedbackEl.classList.add('d-none');
            createFeedbackMessage(feedbackEl, 'info', 'Odosielam...');
            setLoadingState(true);

            try {
                const formData = new FormData(form);

                const headers = {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                };
                // Attach CSRF token header if available
                if (csrfToken) {
                    headers['X-CSRF-TOKEN'] = csrfToken;
                }

                const response = await fetch(form.action, {
                    method: (form.method || 'POST').toUpperCase(),
                    headers: headers,
                    body: formData
                });

                const ct = response.headers.get('content-type') || '';
                let json = null;
                if (ct.includes('application/json')) {
                    json = await response.json();
                } else {
                    // If server didn't return JSON, try to read text for debugging
                    const text = await response.text();
                    throw new Error('Server returned non-JSON response: ' + (text ? text.substring(0, 300) : 'empty'));
                }

                if (!response.ok) {
                    const message = (json && json.message) ? json.message : 'Chyba servera.';
                    throw new Error(message);
                }

                if (json.success) {
                    createFeedbackMessage(feedbackEl, 'success', json.message ?? 'Úspešne uložené.');
                    if (json.redirect) {
                        window.location.assign(json.redirect);
                    }
                } else {
                    createFeedbackMessage(feedbackEl, 'danger', json.errors ?? json.message ?? 'Pri ukladaní nastala chyba.');
                }
            } catch (error) {
                createFeedbackMessage(feedbackEl, 'danger', error.message || 'Nepodarilo sa spojiť so serverom.');
            } finally {
                setLoadingState(false);
            }
        });
    });
});
