CREATE TABLE `events` (
               id INT AUTO_INCREMENT PRIMARY KEY,
               nazov VARCHAR(255) NOT NULL,
               plagat VARCHAR(255),          -- Cesta k obrázku (napr. uploads/plagaty/pf2026.jpg)
               popis TEXT,                   -- Krátky text, ktorý sa zobrazí v modale
               link_prihlasovanie VARCHAR(255), -- URL adresa na registračný formulár
               dokument_propozicie VARCHAR(255), -- Cesta k PDF dokumentu (napr. uploads/docs/propozicie.pdf)
               datum_podujatia DATE,         -- Užitočné pre zoradenie pretekov podľa času
               vytvorene_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;