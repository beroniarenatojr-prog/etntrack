-- Separates uploaded reports into Terminal Report and Progress Report.
-- Existing reports are assigned to 'Terminal Report'.

ALTER TABLE `uploaded_reports`
  ADD COLUMN `report_type` ENUM('Terminal Report','Progress Report') NOT NULL DEFAULT 'Terminal Report' AFTER `report_title`;
