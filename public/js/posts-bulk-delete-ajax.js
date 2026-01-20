document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('select-all-posts');
    const checkboxes = () => Array.from(document.querySelectorAll('.post-checkbox'));
    const bulkBtn = document.getElementById('btn-bulk-delete-posts');
    const countSpan = document.getElementById('selected-count');

    function getCsrfFromButton() {
        if (!bulkBtn) return null;
        return bulkBtn.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('#posts-container')?.dataset.csrf || null;
    }

    function updateBulkUI() {
        const checkedCount = document.querySelectorAll('.post-checkbox:checked').length;
        if (countSpan) countSpan.textContent = checkedCount;
        if (bulkBtn) bulkBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
    }

    function handleDelete(ids) {
        if (!bulkBtn) return;
        const url = bulkBtn.dataset.url;
        const csrf = getCsrfFromButton();
        if (!url) {
            alert('Bulk delete URL not configured.');
            return;
        }

        const doFetch = () => {
            // Use FormData so PHP fills $_POST['ids'] reliably on all servers
            const form = new FormData();
            ids.forEach(id => form.append('ids[]', id));
            // Include CSRF token as header (server reads HTTP_X_CSRF_TOKEN)
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf || ''
                },
                body: form
            }).then(async res => {
                if (!res.ok) {
                    const text = await res.text();
                    throw new Error('Server error: ' + res.status + '\n' + text);
                }
                return res.json();
            }).then(data => {
                if (data.success) {
                    ids.forEach(id => {
                        const el = document.getElementById('post-card-' + id);
                        if (el) el.remove();
                    });
                    updateBulkUI();
                    if (window.Swal) {
                        Swal.fire('Hotovo', data.message || 'Vymazané', 'success');
                    } else {
                        alert(data.message || 'Vymazané');
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire('Chyba', data.message || 'Nepodarilo sa vymazať vybrané.', 'error');
                    } else {
                        alert(data.message || 'Nepodarilo sa vymazať vybrané.');
                    }
                }
            }).catch(err => {
                console.error(err);
                if (window.Swal) {
                    Swal.fire('Chyba', err.message || 'Nepodarilo sa komunikovať so serverom.', 'error');
                } else {
                    alert('Chyba: ' + (err.message || 'Nepodarilo sa komunikovať so serverom.'));
                }
            });
        };

        if (window.Swal) {
            Swal.fire({
                title: 'Hromadné mazanie',
                text: `Naozaj chceš vymazať ${ids.length} vybraných fotiek?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Áno, zmazať!'
            }).then((result) => {
                if (result.isConfirmed) doFetch();
            });
        } else {
            if (confirm(`Naozaj chceš vymazať ${ids.length} vybraných fotiek?`)) doFetch();
        }
    }

    // initial binding and dynamic support
    function bindCheckboxes(){
        checkboxes().forEach(cb => {
            if (!cb.dataset.bound) {
                cb.addEventListener('change', updateBulkUI);
                cb.dataset.bound = '1';
            }
        });
    }

    // Select all
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes().forEach(cb => cb.checked = this.checked);
            updateBulkUI();
        });
    }

    // Bulk button action
    if (bulkBtn) {
        bulkBtn.addEventListener('click', function () {
            const ids = checkboxes().filter(cb => cb.checked).map(cb => cb.value);
            if (ids.length === 0) return;
            handleDelete(ids);
        });
    }

    // initial run
    bindCheckboxes();
    updateBulkUI();

    // If posts can be loaded dynamically later, you can re-run bindCheckboxes() after insertion
});