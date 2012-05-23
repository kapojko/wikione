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

# Обработка действия
function process_action($action,$groupid=0,$recordid=0) {
	global $dbtableprefix;
	switch($action) {
	case 'addgroup':
		$newgroupname=$_POST['name'];
		if($newgroupname) {
			if(!mysql_query("INSERT INTO {$dbtableprefix}groups SET name='".
				mysql_real_escape_string($newgroupname)."'"))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			echo "Группа $newgroupname добавлена.<br>";
			$r=mysql_query("SELECT LAST_INSERT_ID() FROM {$dbtableprefix}groups");
			if(!$r || !mysql_num_rows($r))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			$row=mysql_fetch_row($r);
			$groupid=$row[0];
			$groupname=$newgroupname;
		}
		else {
			echo "Имя группы не задано!<br>";
		}
		break;
	case 'editgroup':
		if(!$groupid)
			{ echo "Error: group id is not given"; mysql_close(); return; }
		$newgroupname=$_POST['name'];
		if($newgroupname) { # переименование
			if(!mysql_query("UPDATE {$dbtableprefix}groups SET name='".
					mysql_real_escape_string($newgroupname)."' 
					WHERE id=$groupid"))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			echo "Группа переименована.<br>";
			$groupname=$newgroupname;
		}
		else { # Удаление
			$r=mysql_query("SELECT id FROM {$dbtableprefix}records
				WHERE groupid=$groupid");
			if(!$r)
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			if(mysql_num_rows($r)) {
				echo "Удалить можно тольку пустую группу!<br>";
			}
			else {
				if(!mysql_query("DELETE FROM {$dbtableprefix}groups 
						WHERE id=$groupid"))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				echo "Группа удалена<br>";
				$groupid=0;
			}
		}
		break;
	case 'addrecord':
		$newrecordtitle=$_POST['title'];
		if($newrecordtitle) {
			$newrecordstar=$_POST['star'];
			$query="INSERT INTO {$dbtableprefix}records SET
				title='".mysql_real_escape_string($newrecordtitle)."',
				star='$newrecordstar',
				created=NOW()";
			if($groupid)
				$query=$query.",groupid=$groupid";
			if(!mysql_query($query))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			echo "Запись добавлена.<br>";
			$r=mysql_query("SELECT LAST_INSERT_ID() FROM {$dbtableprefix}records");
			if(!$r || !mysql_num_rows($r))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			$row=mysql_fetch_row($r);
			$recordid=$row[0];
		}
		else {
			echo "Заголовок записи не задан!<br>";
		}
		break;
	case 'editrecord':
		if(!$recordid)
			{ echo "Error: record id is not given"; mysql_close(); return; }
		$newrecordtitle=$_POST['title'];
		if($newrecordtitle) { # изменение
			$query="UPDATE {$dbtableprefix}records SET groupid='{$_POST['groupid']}',
				title='".mysql_real_escape_string($newrecordtitle)."',
				star='{$_POST['star']}',
				text='".mysql_real_escape_string($_POST['text'])."',
				modified=NOW()
				WHERE id=$recordid";
			if(!mysql_query($query))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			echo "Запись изменена.<br>";
		}
		else { # Удаление
			if($_POST['text'])
				echo "Удалить можно тольку запись без текста!<br>";
			else {
				if(!mysql_query("DELETE FROM {$dbtableprefix}records
						WHERE id=$recordid"))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				echo "Запись удалена<br>";
				$recordid=0;
			}
		}
		break;
	case 'addnote':
		if(!$recordid)
			{ echo "Error: record id is not given"; mysql_close(); return; }
		$notetext=$_POST['text'];
		if($notetext) {
			$query="INSERT INTO {$dbtableprefix}notes(recordid,text,created)
				VALUES('$recordid',
				'".mysql_real_escape_string($notetext)."',
				NOW())";
			if(!mysql_query($query))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			echo "Комментарий добавлен.<br>";
		}
		else {
			echo "Текст комментарий не задан!<br>";
		}
		break;
	}
}

# Вывод заголовка
function out_header($groupid=0) {
	global $title,$dbtableprefix;
	# Заголовок
	echo "<div class='pagetitle'>
		<a href='index.php'>$title</a>
		<a href='blog.php'>Блог</a>
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
			<form action='index.php?action=editgroup&groupid=$groupid'
			method='POST'>
				<input name='name' type='text' value='$groupname'/>
				<input type='submit' value='Сохранить' />
			</form></div>";
	}
	echo "<div id='addgroup' style='display:none'>
		<form action='index.php?action=addgroup' method='POST'>
			<input name='name' type='text'/>
			<input type='submit' value='Добавить группу'/>
		</form>
		</div>
		<div id='addrecord' style='display:none'>
		<form action='index.php?action=addrecord".($groupid ?
			"&groupid=$groupid" : "")."' method='POST' style='margin-top:7px'>
			<input name='title' type='text' size=30 />
			<select name='star'/>
				<option value=0 selected>Заметка</option>
				<option value=1>Дело *</option>
				<option value=2>Дело **</option>
				<option value=3>Дело ***</option>
				<option value=10>Блог</option>
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
		<form action='record.php?action=editrecord&recordid=$recordid".
		($groupid ? "&groupid=$groupid" : "")."' method='POST'>
		<input name='title' type='text' size=70 value='{$row[2]}' />
		<select name='star'/>
			<option value=0 ".($row[3] == 0 ? 'selected' : '').">Заметка</option>
			<option value=1 ".($row[3] == 1 ? 'selected' : '').">Дело *</option>
			<option value=2 ".($row[3] == 2 ? 'selected' : '').">Дело **</option>
			<option value=3 ".($row[3] == 3 ? 'selected' : '').">Дело ***</option>
			<option value=10 ".($row[3] == 10 ? 'selected' : '').">Блог</option>
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
		<form action='record.php?action=addnote&recordid=$recordid".
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