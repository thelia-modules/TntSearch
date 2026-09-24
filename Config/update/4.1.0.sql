# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- tnt_search_log: count how often a term is searched, and when
-- ---------------------------------------------------------------------
-- Each column is added only when missing, so the script can be replayed.
-- Rows logged before this version keep a count of 1 and no dates.

SET @add_column := (SELECT COUNT(*) = 0 FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tnt_search_log' AND `COLUMN_NAME` = 'search_count');
SET @statement := IF(@add_column, 'ALTER TABLE `tnt_search_log` ADD `search_count` INTEGER DEFAULT 1 NOT NULL AFTER `num_hits`', 'DO 0');
PREPARE add_column_statement FROM @statement;
EXECUTE add_column_statement;
DEALLOCATE PREPARE add_column_statement;

SET @add_column := (SELECT COUNT(*) = 0 FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tnt_search_log' AND `COLUMN_NAME` = 'created_at');
SET @statement := IF(@add_column, 'ALTER TABLE `tnt_search_log` ADD `created_at` DATETIME NULL AFTER `search_count`', 'DO 0');
PREPARE add_column_statement FROM @statement;
EXECUTE add_column_statement;
DEALLOCATE PREPARE add_column_statement;

SET @add_column := (SELECT COUNT(*) = 0 FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'tnt_search_log' AND `COLUMN_NAME` = 'updated_at');
SET @statement := IF(@add_column, 'ALTER TABLE `tnt_search_log` ADD `updated_at` DATETIME NULL AFTER `created_at`', 'DO 0');
PREPARE add_column_statement FROM @statement;
EXECUTE add_column_statement;
DEALLOCATE PREPARE add_column_statement;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
