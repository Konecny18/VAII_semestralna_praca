# Inštrukcie a dokumentácia (framework UNIZA) 
Framework má základnú konfiguráciu pre spúšťanie a ladenie webových aplikácií v adresári `<root>/docker`. Všetky potrebné služby sú definované v súbore `docker/docker-compose.yml`. Po ich spustení sa vytvoria nasledujúce služby:

- webový server (Apache) s PHP 8.3
- MariaDB databázový server s vytvorenou databázou pomenovanou podľa premennej prostredia `MYSQL_DATABASE`
- aplikácia Adminer na správu MariaDB


# Inštalácia a spustenie (slovensky)

Tento dokument popisuje rýchly postup, ako projekt získať, nakonfigurovať a spustiť lokálne na Windows alebo v Docker prostredí.

- Prehľad: Projekt je PHP aplikácia s jednoduchým školským frameworkom od UNIZA v adresári `Framework/`. Hlavný vstup je `public/index.php`. Databázové DDL sú v `docker/sql/`.

Checklist (čo urobiť):
- [ ] Nainštalovať závislosti (Composer)
- [ ] Vytvoriť / nakonfigurovať databázu a importovať SQL skripty
- [ ] Nastaviť konfiguráciu pripojenia k DB (pozri ďalej)
- [ ] Vytvoriť priečinok `public/uploads` a nastaviť práva zápisu
- [ ] Spustiť aplikáciu (lokálne alebo pomocou Dockeru)

Požiadavky

- PHP 8.x 
- Composer
- MariaDB (alebo MySQL)
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
5. Docker configuration
Framework má v adresári `docker` (v koreňovom adresári projektu) základnú konfiguráciu na spúšťanie a debugovanie webovej aplikácie. Všetky potrebné služby sú definované v súbore `docker/docker-compose.yml`. Po ich spustení sa vytvoria a spustia nasledujúce služby:

- webový server (Apache) s PHP 8.3
- MariaDB databázový server s vytvorenou databázou pomenovanou podľa premennej prostredia `MYSQL_DATABASE`
- aplikácia Adminer na správu MariaDB

6. Vytvorte priečinok pre nahrané súbory a dajte práva

Aplikácia očakáva priečinok `public/uploads` pre uloženie plagátov a obrázkov. Ak sa stretávate s chybou `move_uploaded_file(...): Failed to open stream: No such file or directory`, skontrolujte:

- že priečinok `public/uploads` existuje
- že webový proces má práva na zápis do tohto priečinka

Príklad (PowerShell):

```powershell
New-Item -ItemType Directory -Path .\public\uploads -Force
# pod Windows: zabezpečte, že účet IIS/Apache/PHP má práva; v Docker kontejnery nastaví práva docker image
```

Spustenie pomocou vstavaného PHP servera (len pre lokálne vývojové účely)

```powershell
cd "$(Get-Location)" # koreň projektu
php -S localhost:8000 -t public
# potom otvorte http://localhost:8000
```
