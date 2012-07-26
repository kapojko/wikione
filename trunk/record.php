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

# Читаем параметры текущего вида
if (isset($_GET['groupid'])) {
	$groupid = $_GET['groupid'];
}
else
	$groupid = 0;
if (isset($_GET['recordid']))
	$recordid = $_GET['recordid'];
else
	$recordid = 0;
$mode = 'tasks';

# Выводим страницу
out_html_header($title);
# Шапка
out_header($groupid, $mode);
#
if (!$recordid) {
	echo "Не передан идентификатор записи.";
	return;
}
# Список записей
echo "<table width=100%><tr><td class='recordlist' width=300px>";
$query = "SELECT id,title,star FROM {$dbtableprefix}records " .
		"WHERE star > 0 AND star < 10";
if ($groupid) {
	$query = $query . " AND groupid=$groupid";
}
$query = $query . " ORDER BY star DESC,modified DESC";
$r = mysql_query($query);
if (!$r) {
	echo "Error: " . mysql_error();
	return;
}
echo "<ol>";
while ($row = mysql_fetch_row($r)) {
	echo "<li><a href='record.php?" . ($groupid ? "groupid=$groupid&" : "") .
	"recordid={$row[0]}'><span class='star{$row[2]}'>
			" . stripslashes($row[1]) . "</span></a></li>";
}
echo "</ol></td>";
# Текущая запись
echo "<td valign=top>";
out_record($recordid);
echo "</td>
		</tr></table>";
# Низ страницы
out_footer();
# Готово
echo "</body></html>";
?>