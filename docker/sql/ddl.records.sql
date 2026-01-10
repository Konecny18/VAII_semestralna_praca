-- DDL for records table
-- Stores performance records for users
-- Assumptions: users.id is INT primary key (as in ddl.users.sql). Using ON DELETE CASCADE so records are removed when a user is deleted.

CREATE TABLE IF NOT EXISTS `records` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `nazov_discipliny` VARCHAR(255) NOT NULL,
  `dosiahnuty_vykon` VARCHAR(255) DEFAULT NULL,
  `datum_vykonu` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `poznamka` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_records_user_id` (`user_id`),
  CONSTRAINT `fk_records_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


# ALTER TABLE `records`
#     MODIFY COLUMN `poznamka` VARCHAR(255) DEFAULT NULL;
