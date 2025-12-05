CREATE TABLE `trainings` (
                             `id` INT NOT NULL AUTO_INCREMENT,
                             `den` ENUM('Pon', 'Uto', 'Str', 'Stv', 'Pia', 'Sob', 'Ned') NOT NULL,
                             `cas_zaciatku` TIME NOT NULL,
                             `cas_konca` TIME NOT NULL,
                             `popis` VARCHAR(100) NOT NULL,
                             PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;
