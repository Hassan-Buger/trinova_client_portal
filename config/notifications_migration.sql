-- Run once on existing installations. Fresh installations already receive
-- these columns from config/database.sql.
ALTER TABLE `notifications`
  ADD COLUMN `title` VARCHAR(160) NULL AFTER `related_entity`,
  ADD COLUMN `message` TEXT NULL AFTER `title`,
  ADD COLUMN `action_url` VARCHAR(255) NULL AFTER `message`;
