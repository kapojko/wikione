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
if (isset($_GET['mode'])) {
	$mode = $_GET['mode'];
}
else {
	$mode = 'tasks';
}
if (isset($_GET['start'])) {
	$start = $_GET['start'];
}
else {
	$start = 0;
}

# Выводим страницу
out_html_header($title);
# Шапка
out_header($groupid,$mode);
# Режим списка
echo "<div class='indexmode'>
	<a class='smalllink' href='index.php?" .
		($groupid ? "groupid=$groupid&" : "") .
		"mode=tasks'>Дела</a>
	<a class='smalllink' href='index.php?" .
		($groupid ? "groupid=$groupid&" : "") .
		"mode=notes'>Записи</a>
	</div>";
# Список записей
echo "<div class='indexrecordlist'>";
if ($mode == 'tasks') { // вывод дел
	// подсчитываем общее количество записей
	$query = "SELECT COUNT(id) FROM {$dbtableprefix}records ".
			"WHERE star > 0 AND star<10";
	if ($groupid) {
		$query = $query . " AND groupid=$groupid";
	}
	$r = mysql_query($query);
	if (!$r) {
		echo "Error: " . mysql_error();
		return;
	}
	$row = mysql_fetch_row($r);
	if (!$row) {
		echo "Error: " . mysql_error();
		return;
	}
	$totalcount = $row[0];

	$query = "SELECT id,title,star FROM {$dbtableprefix}records ".
		"WHERE star > 0 AND star<10";
	if ($groupid) {
		$query = $query . " AND groupid=$groupid";
	}
	$query = $query . " ORDER BY star DESC,modified DESC " .
			"LIMIT $start,$maxindexnotes";;
	$r = mysql_query($query);
	if (!$r) {
		echo "Error: " . mysql_error();
		return;
	}
}
else { // вывод записей
	// подсчитываем общее количество записей
	$query = "SELECT COUNT(id) FROM {$dbtableprefix}records WHERE star=0";
	if ($groupid) {
		$query = $query . " AND groupid=$groupid";
	}
	$r = mysql_query($query);
	if (!$r) {
		echo "Error: " . mysql_error();
		return;
	}
	$row = mysql_fetch_row($r);
	if (!$row) {
		echo "Error: " . mysql_error();
		return;
	}
	$totalcount = $row[0];
	
	$query = "SELECT id,title,star FROM {$dbtableprefix}records WHERE star=0";
	if ($groupid) {
		$query = $query . " AND groupid=$groupid";
	}
	$query = $query . " ORDER BY title ASC " .
			"LIMIT $start,$maxindexnotes";
	$r = mysql_query($query);
	if (!$r) {
		echo "Error: " . mysql_error();
		return;
	}
}
echo "<ol start=" . ($start + 1) . ">";
while ($row = mysql_fetch_row($r)) {
	echo "<li><a href='record.php?" . ($groupid ? "groupid=$groupid&" : "") .
	"recordid={$row[0]}'><span class='star{$row[2]}'>
			" . stripslashes($row[1]) . "</span></a></li>";
}
echo "</ol></div>";

# Ссылки для перехода между страницами
$pagecount = (int)ceil($totalcount/$maxindexnotes);
if ($pagecount > 1) {
	echo "<div class='pagelist'>";
	for ($i = 0; $i < $pagecount; $i = $i + 1) {
		echo "<a class='pagelink' href='index.php?" .
			($groupid ? "groupid=$groupid&" : "") .
			"mode=$mode&start=" . ($i * $maxindexnotes) .
			"'>" . ($i + 1) . "</a> ";
	}
	echo "</div>";
}

# Низ страницы
out_footer();
?>
</body>
</html>