document.addEventListener('DOMContentLoaded', function () {
    const deleteElements = document.querySelectorAll('.delete-btn, .btn-delete-event');

    deleteElements.forEach(element => {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const message = this.getAttribute('data-message') || "Naozaj zmazať?";
            const formId = this.getAttribute('data-form-id');
            const parentForm = this.closest('form');
            const linkUrl = this.getAttribute('href');

            // NOVÉ: Skontrolujeme, či chceme AJAX
            const isAjax = this.getAttribute('data-ajax') === 'true';
            // Identifikátor elementu, ktorý má zmiznúť (napr. ID riadku v tabuľke)
            const targetId = this.getAttribute('data-target-id');

            Swal.fire({
                title: 'Si si istý?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Áno, zmazať!',
                cancelButtonText: 'Zrušiť'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (isAjax) {
                        // --- AJAX LOGIKA ---
                        const url = linkUrl || (parentForm ? parentForm.action : '');

                        fetch(url, {
                            method: 'POST', // Väčšina mazaní v aplikáciách beží cez POST
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    if (targetId) {
                                        document.getElementById(targetId).remove();
                                    }
                                    Swal.fire('Vymazané!', 'Položka bola odstránená.', 'success');
                                } else {
                                    Swal.fire('Chyba!', data.message || 'Nepodarilo sa vymazať.', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('AJAX Error:', error); // Tu sa premenná použije
                                Swal.fire('Chyba!', 'Chyba spojenia so serverom.', 'error');
                            });

                    } else {
                        // --- KLASICKÁ LOGIKA (RELOAD) ---
                        if (formId) {
                            document.getElementById(formId).submit();
                        } else if (parentForm) {
                            parentForm.submit();
                        } else if (linkUrl) {
                            window.location.href = linkUrl;
                        }
                    }
                }
            });
        });
    });
});