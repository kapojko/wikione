<?php
# Авторизация
if($use_authorization && !isset($_SESSION['login'])) {
	echo "<script>
		window.location.href='login.php';
		</script></body></html>";
	return;
}
# Подключение к БД
if(!mysql_connect($dbhost,$dbuser,$dbpwd))
	{ echo "Error connecting DB: ".mysql_error(); return; }
mysql_query("SET NAMES utf8");
if(!mysql_select_db($dbname))
	{ echo "Error selecting DB".mysql_error(); mysql_close(); return; }
# Читаем общие настройки
$r=mysql_query("SELECT title FROM {$dbtableprefix}settings");
if(!$r || !mysql_num_rows($r)) {
	echo "Error reading settings from DB".mysql_error(); mysql_close(); return;
}
$row=mysql_fetch_row($r);
$title=stripslashes($row[0]);
$blogrecperpage=10; # TODO: читать из настроек
# Читаем параметры текущего вида
if(isset($_GET['groupid'])) {
	$groupid=$_GET['groupid'];
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
# Функции
# Вывод заголовка
function out_header($groupid=0) {
	global $title,$dbtableprefix;
	# Выводим сообщение, если есть.
	if(isset($_POST['result']) and isset($_POST['message'])) {
		$result=$_POST['result'];
		$message=$_POST['message'];
		if(!$result)
			echo "ОШИБКА: ";
		echo $message."<br/>";
	}
	# Заголовок
	echo "<div class='pagetitle'>
		<a href='index.php'>$title</a>		
		</div>";
	# Список групп
	echo "<table width=100% class='groups'><tr valign='top'><td>";
	$r=mysql_query("SELECT id,name FROM {$dbtableprefix}groups");
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }		
	while($row=mysql_fetch_row($r)) {
		if($groupid and $row[0] == $groupid) { # текущая группа
			echo "<a class='activegroup'";
			$groupname=stripslashes($row[1]);
		}
		else {
			echo "<a class='group'";
		}
		echo " href='index.php?groupid={$row[0]}'>".stripslashes($row[1])."</a> ";
	}
	echo "</td><td align=right>";
	if($groupid) {
		# Ссылка на редактирование группы
		echo "<span class='pseudolink' 	onclick=
			'document.getElementById(\"editgroup\").style.display=\"block\"'>
			Изменить&nbsp;группу</span>";
	}
	echo "<span class='pseudolink' onclick=
		'document.getElementById(\"addgroup\").style.display=\"block\"'>
		Добавить&nbsp;группу</span>
		<span class='pseudolink' onclick=
		'document.getElementById(\"addrecord\").style.display=\"block\"'>
		Добавить&nbsp;запись</span>";
	if($groupid) {
		# Редактирование группы
		echo "<div id='editgroup' style='display:none'>
			<form action='action.php?action=editgroup&groupid=$groupid'
			method='POST'>
				<input name='name' type='text' value='$groupname'/>
				<input type='submit' value='Сохранить' />
			</form></div>";
	}
	echo "<div id='addgroup' style='display:none'>
		<form action='action.php?action=addgroup' method='POST'>
			<input name='name' type='text'/>
			<input type='submit' value='Добавить группу'/>
		</form>
		</div>
		<div id='addrecord' style='display:none'>
		<form action='action.php?action=addrecord".($groupid ?
			"&groupid=$groupid" : "")."' method='POST' style='margin-top:7px'>
			<input name='title' type='text' size=30 />
			<select name='star'/>
				<option value=0 selected> </option>
				<option value=1>*</option>
				<option value=2>**</option>
				<option value=3>***</option>
			</select>
			<input type='submit' value='Добавить запись' />
		</form>
		</div>
		</td></tr></table>";
}

# Вывод записи
function out_record($recordid) {
	global $dbtableprefix,$creole,$groupid,$action;
	echo "<div class='record'>";
	# Заголовок
	$r=mysql_query("SELECT id,groupid,title,star,text,created,modified 
			FROM {$dbtableprefix}records
			WHERE id=$recordid");
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }
	if(!mysql_num_rows($r))
		{ echo "Error: no record with id=$recordid"; mysql_close(); return; }
	$row=mysql_fetch_row($r);
	echo "<div class='recordtitle'>";
	$groupid1=$row[1];
	if($groupid1) {
		$r1=mysql_query("SELECT name FROM {$dbtableprefix}groups WHERE id=$groupid1");
		if(!$r1)
			{ echo "Error: ".mysql_error(); mysql_close(); return; }
		if(!mysql_num_rows($r))
			{ echo "Error: unexisted group id ($groupid1)."; mysql_close(); return; }
		$row1=mysql_fetch_row($r1);
		$groupname1=$row1[0];
		echo stripslashes($groupname1).": ";
	}
	else
		$groupname1='';
	echo "<span class='star{$row[3]}'>".stripslashes($row[2])."</span></div>";
	# Даты создания и редактирования
	if($row[5] or $row[6]) {
		echo "<div class='recorddate'>";
		if($row[5])
			echo "Создано ".strftime("%d %b %Y %H:%M",strtotime($row[5]));
		if($row[5] and $row[6])
			echo "; ";
		if($row[6])
			echo "Изменено ".strftime("%d %b %Y %H:%M",strtotime($row[6]));
		echo "</div>";
	}
	# Содержание записи
	echo "<div class='recordtext'>";
	echo $creole->parse(stripslashes($row[4]));
	echo "</div>";
	# Редактирование
	echo "<div align=right>
		<span class='pseudolink' onclick=
		'javascript:document.getElementById(\"editrecord\").
		style.display=\"block\"'>
		Редактировать</span>
		<span class='pseudolink' onclick=
		'javascript:document.getElementById(\"addnote\").
		style.display=\"block\"'>Комментировать</span>
		</div>
		<div id='editrecord' style='display:".
		($action == 'addrecord' ? 'block' : 'none')."'>
		<form action='action.php?action=editrecord&recordid=$recordid".
		($groupid ? "&groupid=$groupid" : "")."' method='POST'>
		<input name='title' type='text' size=70 value='{$row[2]}' />
		<select name='star'/>
			<option value=0 ".($row[3] == 0 ? 'selected' : '')."> </option>
			<option value=1 ".($row[3] == 1 ? 'selected' : '').">*</option>
			<option value=2 ".($row[3] == 2 ? 'selected' : '').">**</option>
			<option value=3 ".($row[3] == 3 ? 'selected' : '').">***</option>
		</select>
		<select name='groupid' />
			<option value=0 ".(!$groupid1 ? 'selected' : '')." />";
	$r2=mysql_query("SELECT id,name FROM {$dbtableprefix}groups");
	if(!$r2)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }
	while($row2=mysql_fetch_row($r2))
		echo "<option value={$row2[0]} ".
			($groupid1 == $row2[0] ? 'selected' : '').">
			".stripslashes($row2[1])."</option>";
	echo "</select><br/>
		<textarea name='text' cols=60 rows=10
			style='margin-top:7px;margin-bottom:7px'>";
	echo stripslashes($row[4]);
	echo "</textarea><br />
		<input type='submit' value='Сохранить' />
		</form></div>";
	# Комментарии
	$r=mysql_query("SELECT text,created FROM {$dbtableprefix}notes
			WHERE recordid=$recordid");
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }
	while($row=mysql_fetch_row($r)) {
		echo "<div class='comment'><table><tr>
			<td style='font-style:italic'>".strftime("%d %b %Y %H:%M",
			strtotime($row[1]))."
			<td style='padding-left:20px'>".stripslashes($row[0])."</tr></table></div>";
	}
	# Добавление комментария
	echo "<div id='addnote' align=right style='display:none; margin-top:5px'>
		<form action='action.php?action=addnote&recordid=$recordid".
		($groupid ? "&groupid=$groupid" : "")."' method='POST'>
		<textarea name='text' cols=40 rows=5></textarea><br>
		<input type='submit' value='Комментировать' />
		</form></div>";
	# Готово
	echo "</div>";
}

# Вывод низа страницы
function out_footer() {
	global $use_authorization;
	echo "<hr style='margin-top:20'/>
		<table width=100%><tr>
		<td valign=top>
			<a href='creole_cheat_sheet.html'>Разметка</a> 
			<a href='admin.php'>Администрирование</a> ";
	if($use_authorization)
		echo "<a href='login.php?logout'>Выйти</a>";
	echo "</td>
		<td align=right>WikiOne 2012<br/><img src='olympicmovement.png'/></td>
		</tr></table>";
}
?>