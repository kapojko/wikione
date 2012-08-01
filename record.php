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
$maxindexnotes = $settings['maxindexnotes'];
# Подключаем движок Вики
$creole = load_wiki_engine();

# Читаем параметры текущего вида
if (isset($_GET['recordid'])) {
	$recordid = $_GET['recordid'];
} else {
	echo "Не передан идентификатор записи.";
	return;
}
$r = mysql_query("SELECT groupid, star FROM {$dbtableprefix}records " .
		"WHERE id = $recordid");
$row = mysql_fetch_row($r);
$groupid = $row[0];
$mode = ($row[1] > 0 && $row[1] < 10) ? 'tasks' : 'notes';

# Выводим страницу
out_html_header($title);
# Шапка
out_header($groupid, $mode);

# Список записей
echo "<table width=100%><tr><td class='recordlist' width=300px>";
# подсчитываем общее количество записей
$totalcount = get_total_record_count($groupid, $mode);
# Читаем список записей
$query = get_record_list_query($groupid, $mode, 0, $maxindexnotes);
$r = mysql_query($query);
echo "<ol>";
while ($row = mysql_fetch_row($r)) {
	echo "<li><a href='record.php?" . ($groupid ? "groupid=$groupid&" : "") .
	"recordid={$row[0]}'><span class='star{$row[2]}'>
			" . stripslashes($row[1]) . "</span></a></li>";
}
echo "</ol>";
if ($totalcount > $maxindexnotes) {
	echo "<p>…</p>";
}
echo "</td>";
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