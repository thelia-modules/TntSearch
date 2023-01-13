
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- tnt_search_index
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `tnt_search_index`;

CREATE TABLE `tnt_search_index`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `index` VARCHAR(255) NOT NULL,
    `is_translatable` TINYINT(1) DEFAULT 0 NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1 NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
