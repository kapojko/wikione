<?php 
	include('config.php');
?>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='icon' type='image/png' href='chess-knight.png' />
	<title>Удаление WikiOne <?php echo $wikione_version; ?></title>
</head>
<body>
<?php
	if(!mysql_connect($dbhost,$dbuser,$dbpwd))
		{ echo "Error connecting DB: ".mysql_error(); return; }
	mysql_query('SET NAMES utf8');
	
	if(!mysql_select_db($dbname))
		{ echo "Error selecting DB".mysql_error(); mysql_close(); return; }
		
	/*$query="DROP DATABASE $dbname";
	if(!mysql_query($query)) {
		echo "Error deleting DB: ".mysql_error();
		mysql_close();
		return;
	}*/
	
	if(!mysql_query("DROP TABLE {$dbtableprefix}settings"))
		{ echo "Error: ".mysql_error()."<br>"; }

	if(!mysql_query("DROP TABLE {$dbtableprefix}groups"))
		{ echo "Error: ".mysql_error()."<br>"; }

	if(!mysql_query("DROP TABLE {$dbtableprefix}records"))
		{ echo "Error: ".mysql_error()."<br>"; }

	if(!mysql_query("DROP TABLE {$dbtableprefix}notes"))
		{ echo "Error: ".mysql_error()."<br>"; }
	
	mysql_close();
	echo "Удаление завершено.";
?>
</body>
</html>