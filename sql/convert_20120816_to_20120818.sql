ALTER table `wikione_groups`
    ADD column `order` REAL NOT NULL DEFAULT 0.0;

UPDATE `wikione_groups`
    SET `order` = 0.0;
