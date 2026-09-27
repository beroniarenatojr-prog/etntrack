-- Lets an activity have several pictures (up to 10). One row per picture, in upload order.
-- Copies each activity's existing picture over; the old activities.image column is no longer used.

CREATE TABLE IF NOT EXISTS `activity_images` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `activity_id` INT(11) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `activity_images` (`activity_id`, `file_name`, `sort_order`)
SELECT a.`id`, a.`image`, 0
FROM `activities` a
WHERE a.`image` IS NOT NULL AND a.`image` <> ''
AND NOT EXISTS (SELECT 1 FROM `activity_images` i WHERE i.`activity_id` = a.`id`);
