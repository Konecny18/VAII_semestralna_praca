document.addEventListener('DOMContentLoaded', function () {
    //je const lebo nechcem aby sa menil zoznam obrazkov na nieco ine ako cislo alebo txt
    const zoznamObrazkov = Array.from(document.querySelectorAll('.klikatelny-obrazok'));
    //ked niesu obrazky na stranke tak konci script
    if (!zoznamObrazkov || zoznamObrazkov.length === 0) return; // nothing to do

    //premenna predstavuje samotny obrazok v html je to vnorene v imageModal, pouziva sa na menenie fotky
    const menenieFotiek = document.getElementById('imageModalImg');
    //premenna zastupuje cele vyskakovacie okno (pozadie, krizik, img ...) pouziva sa na kontrolu ci je okno otvorene
    const vyskakovacieOknoModal = document.getElementById('imageModal');
    //ak php nevykresli modal na podstranke tak sa vypne a nevyhodi chybu
    if (!menenieFotiek || !vyskakovacieOknoModal) return; // modal not present on this page

    let currentIndex = 0;

    // Funkcia na zmenu obrázka v modale
    function updateModalImage(index) {
        if (!zoznamObrazkov || zoznamObrazkov.length === 0) return;
        if (index < 0) index = zoznamObrazkov.length - 1; // Ak sme pred prvou, choď na poslednú
        if (index >= zoznamObrazkov.length) index = 0;    // Ak sme za poslednou, choď na prvú

        currentIndex = index;
        menenieFotiek.src = zoznamObrazkov[currentIndex].getAttribute('data-image') || '';
    }

    // Kliknutie na obrázok v galérii
    zoznamObrazkov.forEach((link, index) => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            updateModalImage(index);
        });
    });

    // Ovládanie tlačidlami v modale (ak sú present)
    const prevBtn = document.getElementById('prevImg');
    const nextBtn = document.getElementById('nextImg');
    if (prevBtn) prevBtn.addEventListener('click', () => updateModalImage(currentIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => updateModalImage(currentIndex + 1));

    // Ovládanie šípkami na klávesnici
    document.addEventListener('keydown', function (e) {
        if (!vyskakovacieOknoModal.classList.contains('show')) return;

        if (e.key === 'ArrowLeft') updateModalImage(currentIndex - 1);
        if (e.key === 'ArrowRight') updateModalImage(currentIndex + 1);
    });

    // Vyčistenie
    vyskakovacieOknoModal.addEventListener('hidden.bs.modal', function () {
        menenieFotiek.src = '';
    });
});
