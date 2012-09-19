UPDATE `wikione_settings`
SET `pvalue` = '20120919'
WHERE `pkey` = 'version';

INSERT INTO `wikione_settings` (`pkey`, `pvalue`)
VALUES ('default_kind', 'textile');
