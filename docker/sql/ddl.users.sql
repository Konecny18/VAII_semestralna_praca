CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `meno` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rola` VARCHAR(50) NOT NULL DEFAULT 'user',
  `datum_registracie` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `priezvisko` VARCHAR(255) DEFAULT NULL AFTER `meno`;


-- delete from users;   -- zakomentované, odstránené riziko vymazania dát pri spustení DDL

ALTER TABLE `users`
    CHANGE COLUMN `password_hash` `password` VARCHAR(255) NOT NULL;