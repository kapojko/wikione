USE microtrackdb;
DELETE FROM wikione_settings;
ALTER TABLE wikione_settings
	DROP COLUMN title,
	ADD COLUMN pkey CHAR(50) NOT NULL PRIMARY KEY,
	ADD COLUMN pvalue VARCHAR(255);
INSERT INTO wikione_settings(pkey,pvalue)
	VALUES ('title','Вики-2'),
		('maxindexnotes','100');
