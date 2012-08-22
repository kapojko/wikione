CREATE TABLE wikione_settings (
	pkey CHAR(50) NOT NULL,
	pvalue VARCHAR(255)
);

INSERT INTO wikione_settings(pkey,pvalue)
VALUES ('title','Wikione'),
	('maxindexnotes','100'),
	('version', '20120822');

CREATE TABLE wikione_groups (
	id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name CHAR(30) NOT NULL,
	`order` REAL NOT NULL DEFAULT 0.0
);

CREATE TABLE wikione_records (
	id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
	groupid INTEGER,
	title CHAR(100) NOT NULL,
	star SMALLINT,
	`kind` VARCHAR(10) NOT NULL,
	`rendered_text` TEXT,
	text TEXT,
	created DATETIME,
	modified DATETIME
);

CREATE TABLE wikione_notes (
	id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
	recordid INTEGER,
	text TEXT,
	created DATETIME
);
