
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- tnt_synonym
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `tnt_synonym`;

CREATE TABLE `tnt_synonym`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `term` VARCHAR(255) NOT NULL,
    `group_id` INTEGER NOT NULL,
    `enabled` TINYINT(1) DEFAULT 1 NOT NULL,
    `position` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_synonym` (`term`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
