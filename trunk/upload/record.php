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
if (isset($_GET['recordid'])) {
	$recordid = $_GET['recordid'];
} else {
	echo "Не передан идентификатор записи.";
	return;
}
$record = read_record($recordid);
if (!$record) {
	echo "Error: no record with id=$recordid";
	return;
}
$record_url = get_current_url();

$groupid = $record["groupid"];
$mode = ($record["star"] > 0 && $record["star"] < 10) ? 'tasks' : 'notes';

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
	echo "<li><a href='record.php?recordid={$row[0]}'><span class='star{$row[2]}'>
			" . stripslashes($row[1]) . "</span></a></li>";
}
echo "</ol>";
if ($totalcount > $maxindexnotes) {
	echo "<p>…</p>";
}
echo "</td>";

# Текущая запись
echo "<td valign=top>";
echo "<div class='record'>";
# Заголовок
echo "<div class='recordtitle'>";
if ($record["groupid"]) {
	$r1 = mysql_query("SELECT name FROM {$dbtableprefix}groups WHERE id={$record["groupid"]}");
	if (!mysql_num_rows($r)) {
		echo "Error: unexisted group id ({$record["groupid"]}).";
		return;
	}
	$row1 = mysql_fetch_row($r1);
	$record["groupname"] = stripslashes($row1[0]);
	echo $record["groupname"] . ": ";
}
echo "<span class='star{$record["star"]}'>" . $record["title"] . "</span></div>";
# Даты создания и редактирования
if ($record["created"] or $record["modified"]) {
	echo "<div class='recorddate'>";
	if ($record["created"])
		echo "Создано " . strftime("%d %b %Y %H:%M", strtotime($record["created"]));
	if ($record["created"] and $record["modified"])
		echo "; ";
	if ($record["modified"])
		echo "Изменено " . strftime("%d %b %Y %H:%M", strtotime($record["modified"]));
	echo "</div>";
}
# Содержание записи
echo "<div class='recordtext'>";
if ($record["rendered_text"]) {
	echo $record["rendered_text"];
} else {
	if ($record["kind"] == "creole") {
		$creole = load_wiki_engine("creole");
		echo $creole->parse($record["text"]);
	} else if ($record["kind"] == "textile") {
		$textile = load_wiki_engine("textile");
		echo $textile->TextileThis($record["text"]);
	} else {
		echo $record["text"];
	}
}
echo "</div>";

# Кнопки управления
echo "<div align=right>
	<a class='buttonlink' href='editrecord.php?recordid={$record["id"]}'>
		Редактировать</a>
	<span class='pseudolink' onclick=
		'javascript:document.getElementById(\"addnote\").
		style.display=\"block\"'>Комментировать</span>
	<span class='pseudolink' onclick=
		'javascript:document.getElementById(\"note_link\").
		style.display=\"block\"'>Ссылка</span>
	</div>";

# Добавление комментария
echo "<div id='addnote' align=right style='display:none; margin-top:5px'>
	<form action='action.php?action=addnote&recordid={$record["id"]}" .
($groupid ? "&groupid=$groupid" : "") . "' method='POST'>
	<textarea name='text' cols=40 rows=5></textarea><br>
	<input type='submit' value='Комментировать' />
	</form></div>";
# Ссылка
echo "<div id='note_link' class='note_link' style='display:none'>
	<a href='$record_url'>$record_url</a>";
out_clippy(urlencode($record_url));
echo "</div>";

# Комментарии
echo "<table class='comments'>";
$r = mysql_query("SELECT id,text,created FROM {$dbtableprefix}notes
		WHERE recordid={$record["id"]}");
if (!$r) {
	echo "Error: " . mysql_error();
	return;
}
while ($row = mysql_fetch_row($r)) {
	echo "<tr>
		<td class='comment_header'>" .
			strftime("%d %b %Y %H:%M", strtotime($row[2])) . "<br>
			<a class='buttonlink' href='editnote.php?noteid={$row[0]}'>Изменить</a>
		</td>
		<td class='comment_text'>" . stripslashes($row[1]) . "</td>
		</tr>";
}
echo "</table>";

# Готово
echo "</div>";

echo "</td>
		</tr></table>";
# Низ страницы
out_footer();
# Готово
echo "</body></html>";
?>