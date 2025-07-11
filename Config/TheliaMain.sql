
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- tnt_search_log
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `tnt_search_log`;

CREATE TABLE `tnt_search_log`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `search_words` VARCHAR(255),
    `index` VARCHAR(255),
    `locale` VARCHAR(255),
    `num_hits` INTEGER,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
