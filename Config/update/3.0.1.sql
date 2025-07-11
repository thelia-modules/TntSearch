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