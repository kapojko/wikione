<?php
# Подключение к БД
if(!mysql_connect($dbhost,$dbuser,$dbpwd))
	{ echo "Error connecting DB: ".mysql_error(); return; }
mysql_query("SET NAMES utf8");
if(!mysql_select_db($dbname))
	{ echo "Error selecting DB".mysql_error(); mysql_close(); return; }
# Авторизация
if($use_authorization && !isset($_SESSION['login'])) {
	echo "<script>
		window.location.href='login.php';
		</script></body></html>";
	return;
}
# Читаем общие настройки
$r=mysql_query("SELECT title FROM {$dbtableprefix}settings");
if(!$r || !mysql_num_rows($r)) {
	echo "Error reading settings from DB".mysql_error(); mysql_close(); return;
}
$row=mysql_fetch_row($r);
$title=stripslashes($row[0]);
# Читаем параметры текущего вида
if(isset($_GET['groupid'])) {
	$groupid=$_GET['groupid'];
	$r=mysql_query("SELECT name FROM {$dbtableprefix}groups WHERE id=$groupid");
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }
	if(!mysql_num_rows($r))
		{ echo "Error: no group with id=$groupid"; mysql_close(); return; }
	$row=mysql_fetch_row($r);
	$groupname=$row[0];
}
else
	$groupid=0;
if(isset($_GET['recordid']))
	$recordid=$_GET['recordid'];		
else
	$recordid=0;
# Подключаем движок Вики
require_once('./creole.php');
$creole = new creole(
	array(
		'link_format' => '/index.php?nameid=%s'
		#'interwiki' => array(
		#	'WikiCreole' => 'http://www.wikicreole.org/wiki/%s',
		#	'Wikipedia' => 'http://en.wikipedia.org/wiki/%s'
		#)
	)
);
?>