USE maindb;

ALTER table `wikione_records`
    ADD column `kind` VARCHAR(10) NOT NULL,
    ADD column `rendered_text` TEXT;

UPDATE `wikione_records`
    SET `kind` = 'creole';
