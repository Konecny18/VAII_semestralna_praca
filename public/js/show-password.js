/**
 * Spoločné funkcie pre celú aplikáciu
 */
document.addEventListener('DOMContentLoaded', function() {

    // Funkcia na prepínanie viditeľnosti hesla
    const initPasswordToggle = () => {
        const toggleButtons = document.querySelectorAll('.toggle-password');

        toggleButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Získa ID cieľového inputu z atribútu data-target
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input && icon) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('bi-eye', 'bi-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('bi-eye-slash', 'bi-eye');
                    }
                }
            });
        });
    };

    // Spustíme inicializáciu
    initPasswordToggle();
});