document.addEventListener('DOMContentLoaded', () => {
    // Grab every form that opted into the AJAX helper through the data attribute.
    const ajaxForms = document.querySelectorAll('form[data-ajax-form]');

    // Repaint the feedback area so the user sees success/error info.
    const createFeedbackMessage = (element, type, messages) => {
        if (!element) {
            return;
        }
        const lines = Array.isArray(messages) ? messages : [messages];
        element.innerHTML = lines.map((line) => `· ${line}`).join('<br>');
        element.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
        element.classList.add('alert-' + (type === 'success' ? 'success' : type === 'info' ? 'info' : 'danger'));
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
            feedbackEl?.classList.add('d-none');
            createFeedbackMessage(feedbackEl, 'info', 'Odosielam...');
            setLoadingState(true);

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: (form.method || 'POST').toUpperCase(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const json = await response.json();
                if (!response.ok || json === null) {
                    throw new Error('Nebolo možné spracovať odpoveď servera.');
                }

                if (json.success) {
                    createFeedbackMessage(feedbackEl, 'success', json.message ?? 'Úspešne uložené.');
                    if (json.redirect) {
                        window.location.assign(json.redirect);
                    }
                } else {
                    createFeedbackMessage(feedbackEl, 'danger', json.errors ?? 'Pri ukladaní nastala chyba.');
                }
            } catch (error) {
                createFeedbackMessage(feedbackEl, 'danger', error.message || 'Nepodarilo sa spojiť so serverom.');
            } finally {
                setLoadingState(false);
            }
        });
    });
});
