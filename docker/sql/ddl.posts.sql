-- filepath: snippets/ddl.posts.sql
-- Drops existing 'posts' table (if any) and creates a fresh one.
-- Use this file to recreate the posts table safely.



CREATE TABLE `posts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `albumId` INT NOT NULL,
  `text` TEXT DEFAULT NULL,
  `picture` VARCHAR(300) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_albumId` (`albumId`),
  CONSTRAINT `fk_posts_albums` FOREIGN KEY (`albumId`) REFERENCES `albums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `posts`
    MODIFY COLUMN `text` VARCHAR(255) DEFAULT NULL;