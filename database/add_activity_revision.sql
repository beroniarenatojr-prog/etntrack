-- Adds the "Needs Revision" status for activities and the admin's note to the coordinator.
-- Existing activities keep their current status.

ALTER TABLE `activities`
  MODIFY COLUMN `status` ENUM('approved','pending','rejected','revision') NOT NULL DEFAULT 'pending',
  ADD COLUMN `revision_note` TEXT NULL AFTER `status`;
