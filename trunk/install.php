<?php 
	include('config.php');
?>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='icon' type='image/png' href='chess-knight.png' />
	<title>Установка WikiOne <?php echo $wikione_version; ?></title>
</head>
<body>
<?php
	# Подключение к БД
	if(!mysql_connect($dbhost,$dbuser,$dbpwd))
		{ echo "Error connecting DB: ".mysql_error(); return; }
	mysql_query("SET NAMES utf8");
	if(!mysql_select_db($dbname))
		{ echo "Error selecting DB".mysql_error(); mysql_close(); return; }
		/*$query="CREATE DATABASE $dbname CHARACTER SET utf8";
		if(!mysql_query($query))
			{ echo "Error creating DB: ".mysql_error(); mysql_close(); return; }
		if(!mysql_select_db($dbname))
			{ echo "Error selecting DB".mysql_error(); mysql_close(); return; }*/
	
	$query=
		"CREATE TABLE {$dbtableprefix}settings (
			title CHAR(50),
			email CHAR(30),
			passwordcrypt CHAR(50)
		)";
	if(!mysql_query($query))
		{ echo "Error creating table: ".mysql_error(); mysql_close(); return; }
	$defaultpasswordcrypt=crypt($defaultpassword);
	$query=
		"INSERT INTO {$dbtableprefix}settings(title,email,passwordcrypt)
		VALUES('$defaultname','$defaultemail','$defaultpasswordcrypt')";
	if(!mysql_query($query))
		{ echo "Error: ".mysql_error(); mysql_close(); return; }
	
	$query=
		"CREATE TABLE {$dbtableprefix}groups (
			id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
			name CHAR(30) NOT NULL
		)";
	if(!mysql_query($query))
		{ echo "Error creating table: ".mysql_error(); mysql_close(); return; }
	
	$query=
		"CREATE TABLE {$dbtableprefix}records (
			id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
			groupid INTEGER,
			title CHAR(100) NOT NULL,
			star SMALLINT,
			text TEXT,
			created DATETIME,
			modified DATETIME
		)";
	if(!mysql_query($query))
		{ echo "Error creating table: ".mysql_error(); mysql_close(); return; }

	$query=
		"CREATE TABLE {$dbtableprefix}notes (
			id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
			recordid INTEGER,
			text TEXT,
			created DATETIME
		)";
	if(!mysql_query($query))
		{ echo "Error creating table: ".mysql_error(); mysql_close(); return; }

?>
	Установка завершена.
	<p>Параметры по умолчанию:
	<table border=1><tr>
	<td><b>Заголовок</b></td>
	<td><?php echo $defaultname; ?></td>
	</tr><tr>
	<td><b>E-mail</b></td>
	<td><?php echo $defaultemail; ?></td>
	</tr><tr>
	</tr><tr>
	<td><b>Пароль</b></td>
	<td><?php echo $defaultpassword; ?></td>
	</tr></table>
	<p><a href="index.php">Перейти к главной странице</a>
</body>
</html>