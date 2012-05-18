<?php 
	include('config.php');
	if($use_authorization)
		session_start();
?>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='icon' type='image/png' href='chess-knight.png' />
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>WikiOne <?php echo $wikione_version; ?></title>
</head>
<body>
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
	# Обрабатываем запрошенные действия
	if(isset($_GET['action'])) {
		$action=$_GET['action'];
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
	else
		$action='';

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
	# Выводим страницу
	# Заголовок
	echo "<table><tr><td>
		<a href='index.php'><img src='chess-knight.png' /></a></td>
		<td><h1>$title</h1></td></tr></table>";
	# Список групп
	echo "<table width=100% bgcolor=#FFD4FF><tr valign='top'><td>";
	$r=mysql_query("SELECT id,name FROM {$dbtableprefix}groups");
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }		
	while($row=mysql_fetch_row($r)) {
		if($groupid and $row[0] == $groupid) # текущая группа
			echo "<a class='activegroup'";
		else
			echo "<a class='group'";
		echo " href='index.php?groupid={$row[0]}'>".stripslashes($row[1])."</a> ";
	}
	echo "</td><td align=right>";
		if($groupid) {
			# Ссылка на редактирование группы
			echo "<span class='pseudolink' 	onclick=
				'document.getElementById(\"editgroup\").style.display=\"block\"'>
				Изменить группу</span>";
		}
		echo "<span class='pseudolink' onclick=
			'document.getElementById(\"addgroup\").style.display=\"block\"'>
			Добавить группу</span>";
		if($groupid) {
			# Редактирование группы
			echo "<div id='editgroup' style='display:none'>
				<form action='index.php?action=editgroup&groupid=$groupid'
				method='POST'>
					<input name='name' type='text' value='".stripslashes($groupname)."'/>
					<input type='submit' value='Сохранить' />
				</form></div>";
		}
		echo "<div id='addgroup' style='display:none'>
		<form action='index.php?action=addgroup' method='POST'>
			<input name='name' type='text'/>
			<input type='submit' value='Добавить группу'/>
		</form>
		</div>
		</td></tr></table>";
	# Список записей
	if($recordid)
		echo "<table width=100%><tr><td class='recordlist' width=300px>";
	else
		echo "<table align=center><tr><td class='recordlist'>";
	echo "<h3>";
	if($groupid) {
		echo stripslashes($groupname);
		$query="SELECT id,title,star FROM {$dbtableprefix}records WHERE groupid=$groupid";
	}
	else {
		echo "Все записи";
		$query="SELECT id,title,star FROM {$dbtableprefix}records";
	}
	$query=$query." ORDER BY star DESC";
	$r=mysql_query($query);
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }		
	echo "</h3><a href=#addrecord>
		<p align='right' style='margin-bottom:7px'>&darr;Добавить запись</p></a><ol>";
	while($row=mysql_fetch_row($r)) {
		echo "<li>";
		if($row[2] == 3)
			echo "<b>";
		echo "<a href='index.php?".($groupid ? "groupid=$groupid&" : "").
			"recordid={$row[0]}'>".stripslashes($row[1]);
		if($row[2])
			echo "<sup>".str_repeat('*',$row[2])."</sup></b>";
		echo "</a></li>";
	}
	echo "</ol>
		<a name='addrecord' />
		<form action='index.php?action=addrecord".($groupid ?
			"&groupid=$groupid" : "")."' method='POST' style='margin-top:7px'>
			<input name='title' type='text' size=30 />
			<select name='star'/>
				<option value=0 selected />
				<option value=1>*</option>
				<option value=2>**</option>
				<option value=3>***</option>
			</select>
			<input type='submit' value='Добавить запись' />
		</form></td>";
	# Текущая запись
	if($recordid) {
		echo "<td bgcolor=#FFFFD4 valign=top>";
		# Заголовок
		$r=mysql_query("SELECT id,groupid,title,star,text,created,modified 
				FROM {$dbtableprefix}records
				WHERE id=$recordid");
		if(!$r)
			{ echo "Error: ".mysql_error(); mysql_close(); return; }
		if(!mysql_num_rows($r))
			{ echo "Error: no record with id=$recordid"; mysql_close(); return; }
		$row=mysql_fetch_row($r);
		echo "<h2>";
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
		echo stripslashes($row[2]);
		$star1=$row[3];
		if($star1)
			echo "<sup>".str_repeat('*',$star1)."</sup>";
		echo "</h2>";
		# Даты создания и редактирования
		if($row[5] or $row[6]) {
			echo "<p style='margin-left:20px;font-style:italic'>";
			if($row[5])
				echo "Создано ".strftime("%d %b %Y %H:%M",strtotime($row[5]));
			if($row[5] and $row[6])
				echo "; ";
			if($row[6])
				echo "Изменено ".strftime("%d %b %Y %H:%M",strtotime($row[6]));
		}
		# Содержание записи
		echo $creole->parse(stripslashes($row[4]));
		# Редактирование
		echo "<div align=right>
			<span class='pseudolink' onclick=
			'javascript:document.getElementById(\"editrecord\").
			style.display=\"block\"'>
			Редактировать</span></div>
			<div id='editrecord' style='display:".
			($action == 'addrecord' ? 'block' : 'none')."'>
			<form action='index.php?action=editrecord&recordid=$recordid".
			($groupid ? "&groupid=$groupid" : "")."' method='POST'>
			<input name='title' type='text' size=70 value='{$row[2]}' />
			<select name='star'/>
				<option value=0 ".($star1 == 0 ? 'selected' : '')." />
				<option value=1 ".($star1 == 1 ? 'selected' : '').">*</option>
				<option value=2 ".($star1 == 2 ? 'selected' : '').">**</option>
				<option value=3 ".($star1 == 3 ? 'selected' : '').">***</option>
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
			echo "<table style='margin-left:100px;margin-top:5px;margin-bottom:5px; 
				background-color:#EEEEA4'><tr>
				<td style='font-style:italic'>".strftime("%d %b %Y %H:%M",
				strtotime($row[1]))."
				<td style='padding-left:20px'>".stripslashes($row[0])."</tr></table>";
		}
		# Добавление комментария
		echo "<div align=right style='margin-top:5px'><a name='addnote' />
			<form action='index.php?action=addnote&recordid=$recordid".
			($groupid ? "&groupid=$groupid" : "")."' method='POST'>
			<textarea name='text' cols=40 rows=5></textarea><br>
			<input type='submit' value='Комментировать' />
			</form></div>";
		# Готово
		echo "</td>";
	}
	echo "</tr></table>";
	# Низ страницы
	echo "<hr/>
		<table width=100%><tr>
		<td valign=top>
			<a href='creole_cheat_sheet.html'>Разметка</a> 
			<a href='admin.php'>Администрирование</a> ";
	if($use_authorization)
		echo "<a href='login.php?logout'>Выйти</a>";
	echo "</td>
		<td align=right>WikiOne 2012<br/><img src='olympicmovement.png'/></td>
		</tr></table>";
	# Готово
	mysql_close();
?>
</body>
</html>