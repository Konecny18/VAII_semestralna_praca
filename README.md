# Inštalácia a spustenie (slovensky)

Tento dokument popisuje rýchly postup, ako projekt získať, nakonfigurovať a spustiť lokálne na Windows alebo v Docker prostredí.

- Prehľad: Projekt je PHP aplikácia s vlastným jednoduchým frameworkom v adresári `Framework/`. Hlavný vstup je `public/index.php`. Databázové DDL sú v `docker/sql/`.

Checklist (čo urobiť):
- [ ] Nainštalovať závislosti (Composer)
- [ ] Vytvoriť / nakonfigurovať databázu a importovať SQL skripty
- [ ] Nastaviť konfiguráciu pripojenia k DB (pozri ďalej)
- [ ] Vytvoriť priečinok `public/uploads` a nastaviť práva zápisu
- [ ] Nastaviť limity nahrávania v `php.ini` ak potrebné
- [ ] Spustiť aplikáciu (lokálne alebo pomocou Dockeru)

Požiadavky

- PHP 7.4+ alebo 8.x (rozhodne skontrolujte, čo používa váš hosting)
- Composer
- MySQL (alebo MariaDB)
- (Voliteľné) Docker + docker-compose

Rýchla inštalácia lokálne (Windows PowerShell)

1. Klonovanie repozitára

```powershell
git clone <repository-url>
cd "VAII_semestralna_praca"
```

2. Inštalácia PHP závislostí (ak používate Composer)

```powershell
composer install
```

3. Konfigurácia databázy

- Projekt nemá univerzálne .env v koreňovom adresári (skontrolujte), preto upravte konfiguráciu DB v súbore `App/Configuration.php` (alebo inej konfiguračnej sekcii vo vašom repozitári) tak, aby zodpovedala lokálnej inštancii MySQL.

4. Vytvorenie databázových tabuliek

- SQL skripty sú v `docker/sql/` (napr. `ddl.users.sql`, `ddl.events.sql` a pod.). Importujte ich do vašej DB:

```powershell
mysql -u dbuser -p dbname < "docker/sql/ddl.users.sql"
mysql -u dbuser -p dbname < "docker/sql/ddl.events.sql"
# opakujte pre ďalšie ddl.*.sql súbory
```

Ak používate Docker, môžete použiť `docker-compose` (vytvorí DB a web službu podľa `docker/docker-compose.yml`):

```powershell
cd docker
docker-compose up -d
```

5. Vytvorte priečinok pre nahrané súbory a dajte práva

Aplikácia očakáva priečinok `public/uploads` pre uloženie plagátov a obrázkov. Ak sa stretávate s chybou `move_uploaded_file(...): Failed to open stream: No such file or directory`, skontrolujte:

- že priečinok `public/uploads` existuje
- že webový proces má práva na zápis do tohto priečinka

Príklad (PowerShell):

```powershell
New-Item -ItemType Directory -Path .\public\uploads -Force
# pod Windows: zabezpečte, že účet IIS/Apache/PHP má práva; v Docker kontejnery nastaví práva docker image
```

Nastavenie limitov nahrávania (ak sa stretávate s chybou typu "POST Content-Length exceeds the limit" alebo "The uploaded file exceeds the upload_max_filesize")

Zmeňte hodnoty v `php.ini` alebo v konfigurácii PHP použitom v Docker obraze (napr. `upload_max_filesize`, `post_max_size`):

- upload_max_filesize = 2M         ; (alebo iná požadovaná veľkosť)
- post_max_size = 2M
- max_input_time = 60
- memory_limit = 128M

Po zmene php.ini reštartujte web server / PHP-FPM / docker-compose.

Konkrétne pre vaše potreby: ak chcete limit 2 MB pre plagáty, nastavte `upload_max_filesize` a `post_max_size` na 2M.

Bezpečnosť / ďalšie nastavenia

- Overenia na strane servera: Skontrolujte, že vo formulároch máte server-side validáciu uploadovaných súborov (typ súboru, MIME type, veľkosť) a validáciu dátumu (napr. event musí byť v budúcnosti).
- CSRF: Overte prítomnosť ochrany proti CSRF (skontrolujte v `Framework/Http/Request.php` alebo kontroléri, či sa používajú tokeny). Ak to framework nepridáva automaticky, pridajte jednoduchý token systém.
- SQL injection: Projekt už podľa vašich poznámok rieši SQL injection cez pripravené dotazy a binding — uistite sa, že všetky modely používajú parametrizované dotazy.

Spustenie pomocou vstavaného PHP servera (len pre lokálne vývojové účely)

```powershell
cd "$(Get-Location)" # koreň projektu
php -S localhost:8000 -t public
# potom otvorte http://localhost:8000
```

Debug a bežné chyby

- "move_uploaded_file(...): Failed to open stream: No such file or directory" — vyriešiť vytvorením `public/uploads` a správnymi právami.
- "POST Content-Length of ... exceeds the limit" — upraviť `post_max_size` a `upload_max_filesize` v `php.ini`.
- "Unable to resolve table 'users'" alebo iné chyby týkajúce sa chýbajúcich tabuliek — skontrolujte pripojenie k DB a načítanie DDL skriptov.
- "Class ... not found" — spustiť `composer dump-autoload` alebo skontrolovať `Framework/ClassLoader.php` a cesty k súborom.
- Session/headers chyby ako `session_start(): Session cannot be started after headers have already been sent` — zabezpečte, že pred volaním `session_start()` sa nevypisuje žiaden výstup (žiadne echo, biele znaky pred `<?php` atď.).

Tipy pre vývoj

- Po úpravách modelov alebo autoload konfigurácie spustite `composer dump-autoload`.
- Ak používate Docker, upravte `docker/php` alebo príslušný Dockerfile, ak potrebujete zmeniť `php.ini`.
- Databázové migrácie a DDL sú v `docker/sql/` — použite ich ako zdroj pri vytváraní schémy.

Kontakt a ďalšie kroky

Ak chcete, môžem doplniť README o konkrétne príkazy pre nastavenie Docker obrazu, alebo pripraviť skript na import všetkých `ddl.*.sql` súborov naraz. Povedzte, ktorú možnosť preferujete.
