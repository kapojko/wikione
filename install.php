<?php
include('config.php');
include('common.php');

if ($use_authorization && !check_authorization()) {
	return;
}
# Подключение к БД
if (!connect_to_db($dbhost, $dbuser, $dbpwd, $dbname)) {
	return;
}
# Читаем настройки
$settings = load_settings($dbtableprefix);
$title = $settings['title'];
# Подключаем движок Вики
$creole = load_wiki_engine();

/* $query="CREATE DATABASE $dbname CHARACTER SET utf8";
  if(!mysql_query($query))
  { echo "Error creating DB: ".mysql_error(); return; }
  if(!mysql_select_db($dbname))
  { echo "Error selecting DB".mysql_error(); return; } */

$query =
		"CREATE TABLE {$dbtableprefix}settings (
			pkey CHAR(50) NOT NULL,
			pvalue VARCHAR(255)
		)";
if (!mysql_query($query)) {
	echo "Error creating table: " . mysql_error();
	return;
}
$defaultpasswordcrypt = crypt($defaultpassword);
$query =
		"INSERT INTO {$dbtableprefix}settings(pkey,pvalue)
		VALUES ('title','Wikione'),
			('maxindexnotes','100'),
			('version', '20120818')";
if (!mysql_query($query)) {
	echo "Error: " . mysql_error();
	return;
}

$query =
		"CREATE TABLE {$dbtableprefix}groups (
			id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
			name CHAR(30) NOT NULL,
			`order` REAL NOT NULL DEFAULT 0.0
		)";
if (!mysql_query($query)) {
	echo "Error creating table: " . mysql_error();
	return;
}

$query =
		"CREATE TABLE {$dbtableprefix}records (
			id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
			groupid INTEGER,
			title CHAR(100) NOT NULL,
			star SMALLINT,
			`kind` VARCHAR(10) NOT NULL,
			`rendered_text` TEXT,
			text TEXT,
			created DATETIME,
			modified DATETIME
		)";
if (!mysql_query($query)) {
	echo "Error creating table: " . mysql_error();
	return;
}

$query =
		"CREATE TABLE {$dbtableprefix}notes (
			id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
			recordid INTEGER,
			text TEXT,
			created DATETIME
		)";
if (!mysql_query($query)) {
	echo "Error creating table: " . mysql_error();
	return;
}
?>
<html>
	<head>
		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
		<link rel='icon' type='image/png' href='chess-knight.png' />
		<title>Установка WikiOne <?php echo $wikione_version; ?></title>
	</head>
	<body>
		Установка завершена.
		<p><a href="index.php">Перейти к главной странице</a>
	</body>
</html>