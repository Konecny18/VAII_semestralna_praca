document.addEventListener('DOMContentLoaded', function () {
    // Sledujeme tvoje pôvodné .delete-btn aj nové .btn-delete-event
    const deleteElements = document.querySelectorAll('.delete-btn, .btn-delete-event');

    deleteElements.forEach(element => {
        element.addEventListener('click', function (event) {
            // Zastavíme odoslanie aj zatvorenie dropdown menu
            event.preventDefault();
            event.stopPropagation();

            const message = this.getAttribute('data-message') || "Naozaj zmazať?";
            const formId = this.getAttribute('data-form-id'); // Pre externé formuláre
            const parentForm = this.closest('form'); // Pre tlačidlá vnútri formulára
            const linkUrl = this.getAttribute('href'); // Pre klasické odkazy <a>

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
                    if (formId) {
                        // Ak má tlačidlo data-form-id, odošleme ten konkrétny formulár
                        document.getElementById(formId).submit();
                    } else if (parentForm) {
                        parentForm.submit();
                    } else if (linkUrl) {
                        window.location.href = linkUrl;
                    }
                }
            });
        });
    });
});