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

$record_url = get_current_url();

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
# 
	
echo "<div class='record'>";
# Заголовок
$r = mysql_query("SELECT id,groupid,title,star,text,created,modified 
		FROM {$dbtableprefix}records
		WHERE id=$recordid");
if (!$r) {
	echo "Error: " . mysql_error();
	return;
}
if (!mysql_num_rows($r)) {
	echo "Error: no record with id=$recordid";
	return;
}
$row = mysql_fetch_row($r);
echo "<div class='recordtitle'>";
$groupid1 = $row[1];
if ($groupid1) {
	$r1 = mysql_query("SELECT name FROM {$dbtableprefix}groups WHERE id=$groupid1");
	if (!$r1) {
		echo "Error: " . mysql_error();
		return;
	}
	if (!mysql_num_rows($r)) {
		echo "Error: unexisted group id ($groupid1).";
		return;
	}
	$row1 = mysql_fetch_row($r1);
	$groupname1 = $row1[0];
	echo stripslashes($groupname1) . ": ";
}
else
	$groupname1 = '';
echo "<span class='star{$row[3]}'>" . stripslashes($row[2]) . "</span></div>";
# Даты создания и редактирования
if ($row[5] or $row[6]) {
	echo "<div class='recorddate'>";
	if ($row[5])
		echo "Создано " . strftime("%d %b %Y %H:%M", strtotime($row[5]));
	if ($row[5] and $row[6])
		echo "; ";
	if ($row[6])
		echo "Изменено " . strftime("%d %b %Y %H:%M", strtotime($row[6]));
	echo "</div>";
}
# Содержание записи
echo "<div class='recordtext'>";
echo $creole->parse(stripslashes($row[4]));
echo "</div>";

# Кнопки управления
echo "<div align=right>
	<span class='pseudolink' onclick=
	'javascript:document.getElementById(\"editrecord\").
	style.display=\"block\"'>
	Редактировать</span>
	<span class='pseudolink' onclick=
	'javascript:document.getElementById(\"addnote\").
	style.display=\"block\"'>Комментировать</span>
	<span class='pseudolink' onclick=
	'javascript:document.getElementById(\"note_link\").
	style.display=\"block\"'>Ссылка</span>
	</div>";

# Ссылка
echo "<div id='note_link' class='note_link' style='display:none'>
	<a href='$record_url'>$record_url</a>";
out_clippy($record_url);
echo "</div>";

# Редактирование
echo "<div id='editrecord' style='display:none'>
	<form action='action.php?action=editrecord&recordid=$recordid" .
		($groupid ? "&groupid=$groupid" : "") . "' method='POST'>
	<input name='title' id='edit_record_title_$recordid' type='text'
		size=70 value='" . stripslashes($row[2]) . "' />
	<select name='star'/>
		<option value=0 " . ($row[3] == 0 ? 'selected' : '') . "> </option>
		<option value=1 " . ($row[3] == 1 ? 'selected' : '') . ">*</option>
		<option value=2 " . ($row[3] == 2 ? 'selected' : '') . ">**</option>
		<option value=3 " . ($row[3] == 3 ? 'selected' : '') . ">***</option>
	</select>
	<select name='groupid' />
		<option value=0 " . (!$groupid1 ? 'selected' : '') . " />";

$r2 = mysql_query("SELECT id,name FROM {$dbtableprefix}groups");
if (!$r2) {
	echo "Error: " . mysql_error();
	return;
}
while ($row2 = mysql_fetch_row($r2))
	echo "<option value={$row2[0]} " .
	($groupid1 == $row2[0] ? 'selected' : '') . ">
		" . stripslashes($row2[1]) . "</option>";
echo "</select>
	<span class='pseudolink' onclick='
		document.getElementById(\"edit_record_title_$recordid\").value=\"\",
		document.getElementById(\"edit_record_text_$recordid\").value=\"\"'>
		Очистить</span>
	<br/>
	<textarea name='text' id='edit_record_text_$recordid' cols=60 rows=10
		style='margin-top:7px;margin-bottom:7px'>";
echo stripslashes($row[4]);
echo "</textarea><br />
	<input type='submit' value='Сохранить' />
	</form></div>";
# Комментарии
$r = mysql_query("SELECT text,created FROM {$dbtableprefix}notes
		WHERE recordid=$recordid");
if (!$r) {
	echo "Error: " . mysql_error();
	return;
}
while ($row = mysql_fetch_row($r)) {
	echo "<div class='comment'><table><tr>
		<td style='font-style:italic'>" . strftime("%d %b %Y %H:%M", strtotime($row[1])) . "
		<td style='padding-left:20px'>" . stripslashes($row[0]) . "</tr></table></div>";
}
# Добавление комментария
echo "<div id='addnote' align=right style='display:none; margin-top:5px'>
	<form action='action.php?action=addnote&recordid=$recordid" .
($groupid ? "&groupid=$groupid" : "") . "' method='POST'>
	<textarea name='text' cols=40 rows=5></textarea><br>
	<input type='submit' value='Комментировать' />
	</form></div>";
# Готово
echo "</div>";

echo "</td>
		</tr></table>";
# Низ страницы
out_footer();
# Готово
echo "</body></html>";
?>