/**
 * show-move-posts.js
 *
 * Skript pre galériu príspevkov: otváranie obrázka v modale, navigácia medzi obrázkami (šípky a tlačidlá)
 * a vyčistenie modalu pri zatvorení.
 *
 * - Očakáva, že v HTML sú prvky s triedou `.klikatelny-obrazok` s atribútom `data-image` obsahujúcim URL obrázka.
 * - Modal obsahuje prvky s id `imageModal`, `imageModalImg`, `prevImg`, `nextImg`.
 * - Podporuje klávesové šípky (ArrowLeft, ArrowRight).
 */

document.addEventListener('DOMContentLoaded', function () {
    //je const lebo nechcem aby sa menil zoznam obrazkov na nieco ine ako cislo alebo txt
    // Vytvorí pole (Array) zo všetkých elementov, ktoré majú triedu .klikatelny-obrazok
    // Array.from je dôležité, aby sme mohli používať funkcie ako .length alebo indexy
    const zoznamObrazkov = Array.from(document.querySelectorAll('.klikatelny-obrazok'));

    // Ak na stránke nie sú žiadne obrázky (napr. prázdny album), skript sa tu zastaví a nič nerobí
    if (!zoznamObrazkov || zoznamObrazkov.length === 0) return; // nothing to do

    // Referencia na <img> tag vo vnútri modalu, kde budeme meniť atribút 'src'
    const menenieFotiek = document.getElementById('imageModalImg');
    //premenna zastupuje cele vyskakovacie okno (pozadie, krizik, img ...) pouziva sa na kontrolu ci je okno otvorene
    const vyskakovacieOknoModal = document.getElementById('imageModal');
    // Poistka: Ak na stránke chýba HTML pre modal, skript skončí, aby nevyhodil chybu (null error)
    if (!menenieFotiek || !vyskakovacieOknoModal) return; // modal not present on this page

    // Premenná, ktorá si pamätá, ktorú fotku v poradí máme práve otvorenú (0 je prvá)
    let currentIndex = 0;

    // Funkcia na zmenu obrázka v modale
    function updateModalImage(index) {
        // Ochrana pred prázdnym zoznamom
        if (!zoznamObrazkov || zoznamObrazkov.length === 0) return;

        // "KOLOTOČ" (Infinite Loop):
        // Ak som na prvej fotke a stlačím "doľava", skočí to na poslednú fotku
        if (index < 0) index = zoznamObrazkov.length - 1; // Ak sme pred prvou, choď na poslednú

        // Ak som na poslednej fotke a stlačím "doprava", skočí to na prvú
        if (index >= zoznamObrazkov.length) index = 0;    // Ak sme za poslednou, choď na prvú

        // Aktualizujeme index, aby sme vedeli, kde sme
        currentIndex = index;
        // Zoberieme URL adresu z data-atribútu 'data-image' a vložíme ju do <img> v modale
        menenieFotiek.src = zoznamObrazkov[currentIndex].getAttribute('data-image') || '';
    }

    // Kliknutie na obrázok v galérii
    zoznamObrazkov.forEach((link, index) => {
        link.addEventListener('click', function (e) {
            // Zastaví odkaz, aby neotvoril obrázok v novom okne/tabe
            e.preventDefault();
            // Zavolá funkciu s indexom kliknutého obrázka
            updateModalImage(index);
        });
    });

    // Ovládanie tlačidlami v modale (ak sú present)
    const prevBtn = document.getElementById('prevImg');
    const nextBtn = document.getElementById('nextImg');
    // Pri kliknutí vypočíta index (aktuálny +/- 1) a zavolá zmenu
    if (prevBtn) prevBtn.addEventListener('click', () => updateModalImage(currentIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => updateModalImage(currentIndex + 1));

    // Ovládanie šípkami na klávesnici
    document.addEventListener('keydown', function (e) {
        // DÔLEŽITÉ: Šípky fungujú len vtedy, ak je modal otvorený (má triedu 'show')
        // Inak by si šípkami v galérii listoval aj vtedy, keď ju neprezeráš
        if (!vyskakovacieOknoModal.classList.contains('show')) return;

        if (e.key === 'ArrowLeft') updateModalImage(currentIndex - 1);
        if (e.key === 'ArrowRight') updateModalImage(currentIndex + 1);
    });

    // Keď sa modal zavrie (udalosť Bootstrapu 'hidden.bs.modal')
    vyskakovacieOknoModal.addEventListener('hidden.bs.modal', function () {
        // Resetujeme zdroj obrázka, aby pri ďalšom otvorení modalu
        // nepreblikla na zlomok sekundy stará fotka, kým sa načítava nová.
        menenieFotiek.src = '';
    });
});
