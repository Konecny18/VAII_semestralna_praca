(function(){
    'use strict';

    function debounce(fn, delay) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    document.addEventListener('DOMContentLoaded', function(){
        // --- EMAIL VALIDÁCIA (pôvodná + AJAX) ---
        const emailInput = document.getElementById('email');
        if (emailInput) {
            const emailFeedback = document.createElement('div');
            emailFeedback.className = 'invalid-feedback';
            emailInput.parentNode.appendChild(emailFeedback);

            const checkUrl = window.__CHECK_EMAIL_URL__ || '/auth/checkEmail';

            const doCheckEmail = debounce(function(){
                const val = emailInput.value.trim();
                if (val === '') {
                    emailInput.classList.remove('is-invalid','is-valid');
                    return;
                }
                const re = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
                if (!re.test(val)) {
                    emailInput.classList.add('is-invalid');
                    emailFeedback.textContent = 'Zadajte platný email.';
                    return;
                }

                const separator = checkUrl.includes('?') ? '&' : '?';
                fetch(checkUrl + separator + 'email=' + encodeURIComponent(val))
                    .then(r => r.json())
                    .then(json => {
                        if (json.exists) {
                            emailInput.classList.add('is-invalid');
                            emailFeedback.textContent = 'Email je už registrovaný.';
                        } else {
                            emailInput.classList.remove('is-invalid');
                            emailInput.classList.add('is-valid');
                        }
                    });
            }, 400);

            emailInput.addEventListener('input', doCheckEmail);
        }

        // --- VALIDÁCIA HESLA
        const passInput = document.getElementById('password');
        const passConfirmInput = document.getElementById('password_confirm');

        if (passInput) {
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
                // Vždy skontrolovať zhodu, keď sa zmení hlavné heslo
                if (passConfirmInput) validateConfirm();
            };

            passInput.addEventListener('input', debounce(validatePassword, 400));
        }

        // --- KONTROLA ZHODY HESIEL ---
        if (passConfirmInput) {
            const confirmFeedback = document.createElement('div');
            confirmFeedback.className = 'invalid-feedback';
            passConfirmInput.parentNode.appendChild(confirmFeedback);

            const validateConfirm = () => {
                if (passConfirmInput.value === '') {
                    passConfirmInput.classList.remove('is-invalid', 'is-valid');
                } else if (passConfirmInput.value !== passInput.value) {
                    passConfirmInput.classList.add('is-invalid');
                    passConfirmInput.classList.remove('is-valid');
                    confirmFeedback.textContent = 'Heslá sa nezhodujú!';
                } else {
                    passConfirmInput.classList.remove('is-invalid');
                    passConfirmInput.classList.add('is-valid');
                }
            };

            passConfirmInput.addEventListener('input', validateConfirm);
        }

        // --- OCHRANA PRED ODOSLANÍM ---
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e){
                const invalids = form.querySelectorAll('.is-invalid');
                if (invalids.length > 0) {
                    e.preventDefault();
                    invalids[0].focus();
                }
            });
        }
    });
})();