USE microtrackdb;
CREATE TABLE wikione_settings (
	title CHAR(50)
);
INSERT INTO wikione_settings(title)
	VALUES('Вики-1');
ALTER TABLE groups
	RENAME TO wikione_groups;
ALTER TABLE records
	ADD COLUMN created DATETIME,
	ADD COLUMN modified DATETIME,
	RENAME TO wikione_records;
CREATE TABLE wikione_notes (
	id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
	recordid INTEGER,
	text TEXT,
	created DATETIME
);
