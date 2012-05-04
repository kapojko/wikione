<html encoding="UTF-8">
<body>
<?php
# Comment next line to get "delete" script.
return;
	$dbuser='root';
	$dbpwd='';
	$dbname='microtrackdb';
	$title='Вики';
	
	$linkid=mysql_connect('localhost',$dbuser,$dbpwd);
	if(!$linkid) {
		echo "Error connecting DB: ".mysql_error();
		return;
	}
	mysql_query('SET NAMES utf8');
	if(!mysql_select_db($dbname)) {
		echo 'No DB, do nothing';
		mysql_close();
		return;
	}
	$query="DROP DATABASE $dbname";
	if(!mysql_query($query)) {
		echo "Error deleting DB: ".mysql_error();
		mysql_close();
		return;
	}
	mysql_close($linkid);
	echo 'DB deleted';
?>
</body>
</html>