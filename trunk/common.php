<?php

# Подключение к БД

function connect_to_db($dbhost, $dbuser, $dbpwd, $dbname) {
	if (!mysql_connect($dbhost, $dbuser, $dbpwd)) {
		echo "Error connecting DB: " . mysql_error();
		return FALSE;
	}
	mysql_query("SET NAMES utf8");
	if (!mysql_select_db($dbname)) {
		echo "Error selecting DB" . mysql_error();
		return FALSE;
	}
	return TRUE;
}

# Проверка авторизации. При ошибке выполняет редирект.
function check_authorization() {
	session_start();
	if (!isset($_SESSION['login'])) {
		header('Location: login.php');
		return FALSE;
	}
	return TRUE;
}

# Чтение настроек
function load_settings($dbtableprefix) {
	$r = mysql_query("SELECT pkey,pvalue FROM {$dbtableprefix}settings");
	if (!$r) {
		echo "Error reading settings from DB" . mysql_error();
		return array();
	}
	while ($row = mysql_fetch_row($r)) {
		$settings[$row[0]] = stripslashes($row[1]);
	}
	return $settings;
}

# Чтение записи
function read_record($recordid) {
	global $dbtableprefix;
	
	$r = mysql_query("SELECT groupid,title,star,kind,text,rendered_text,created,modified 
		FROM {$dbtableprefix}records
		WHERE id=$recordid");
	if (!mysql_num_rows($r)) {
		return;
	}
	$row = mysql_fetch_row($r);
	$record = array(
		"id" => $recordid,
		"groupid" => $row[0],
		"groupname" => NULL,
		"title" => stripslashes($row[1]),
		"star" => $row[2],
		"kind" => $row[3],
		"text" => stripslashes($row[4]),
		"rendered_text" => stripslashes($row[5]),
		"created" => $row[6],
		"modified" => $row[7]
	);
	
	return $record;
}

# Подключение Вики-движка
function load_wiki_engine() {
	require_once('./creole.php');
	return new creole(
			array(
				'link_format' => '/index.php?nameid=%s'
			#'interwiki' => array(
			#	'WikiCreole' => 'http://www.wikicreole.org/wiki/%s',
			#	'Wikipedia' => 'http://en.wikipedia.org/wiki/%s'
			#)
			)
	);
}

# Получение текущего URL скрипта
function get_current_url(){  
	$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https')
			=== FALSE ? 'http' : 'https';
	$host = $_SERVER['HTTP_HOST'];
	$script   = $_SERVER['SCRIPT_NAME'];
	$params   = $_SERVER['QUERY_STRING'];
	$currentUrl = $protocol . '://' . $host . $script . '?' . $params;
	return $currentUrl;
}

# Вывод кнопки копирования (текст не должен содержать спец. символов!)
function out_clippy($text, $bgcolor='#FFF') {
	echo <<<END
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
	width="110"
	height="14"
	id="clippy" >
<param name="movie" value="clippy.swf"/>
<param name="allowScriptAccess" value="always" />
<param name="quality" value="high" />
<param name="scale" value="noscale" />
<param NAME="FlashVars" value="text=$text">
<param name="bgcolor" value="$bgcolor">
<embed src="clippy.swf"
	width="110"
	height="14"
	name="clippy"
	quality="high"
	allowScriptAccess="always"
	type="application/x-shockwave-flash"
	pluginspage="http://www.macromedia.com/go/getflashplayer"
	FlashVars="text=$text"
	bgcolor="$bgcolor"
/>
</object>
END;
}

# Вывод заголовка HTML

function out_html_header($title) {
	echo "<html>
		<head>
			<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
			<link rel='icon' type='image/png' href='chess-knight.png' />
			<link rel='stylesheet' type='text/css' href='style.css' />
			<title>$title</title>
		</head>
		<body>";
}

# Вывод возможно выбранного текста
function out_selected($text, $selected) {
	if ($selected) {
		echo "<span class='selected'>";
	}
	echo $text;
	if ($selected) {
		echo "</span>";
	}
}

# Вывод заголовка

function out_header($groupid, $mode) {
	global $title, $dbtableprefix;
	# Выводим сообщение, если есть.
	if (isset($_POST['result']) and isset($_POST['message'])) {
		$result = $_POST['result'];
		$message = $_POST['message'];
		if (!$result)
			echo "ОШИБКА: ";
		echo $message . "<br/>";
	}
	# Заголовок
	echo "<div class='pagetitle'>
		<a href='index.php?mode=$mode'>$title</a>		
		</div>";
	# Список групп
	echo "<table width=100% class='groups'><tr valign='top'><td>";
	$r = mysql_query("SELECT id,name FROM {$dbtableprefix}groups");
	if (!$r) {
		echo "Error: " . mysql_error();
		return;
	}
	while ($row = mysql_fetch_row($r)) {
		$curgroupname = stripslashes($row[1]);
		echo "<a class='group' href='index.php?groupid={$row[0]}&mode=$mode'>";
		out_selected($curgroupname, $groupid and $row[0] == $groupid);
		echo "</a> ";
	}
	echo "</td><td align=right>";
	if ($groupid) {
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
	if ($groupid) {
		# Редактирование группы
		echo "<div id='editgroup' style='display:none'>
			<form action='action.php?action=editgroup&groupid=$groupid&mode=$mode'
			method='POST'>
				<input name='name' id='edit_group_name' type='text' value='$groupname'/>
				<span class='pseudolink' onclick=
					'document.getElementById(\"edit_group_name\").value=\"\"'>
					Очистить</span>
				<input type='submit' value='Сохранить' />
			</form></div>";
	}
echo <<<EOT
	<div id="addgroup" style="display:none">
		<form action="action.php?action=addgroup" method="POST">
			<input name="name" type="text"/>
			<input type="submit" value="Добавить группу"/>
		</form>
	</div>
	<div id="addrecord" style="display:none">
		<form action="action.php?action=addrecord" method="POST"
				style="margin-top:7px">
			<input name="title" type="text" size=30 />
			<input name="groupid" type="hidden" value="$groupid"></input>
			<select name="star"/>
				<option value=0 selected> </option>
				<option value=1>*</option>
				<option value=2>**</option>
				<option value=3>***</option>
			</select>
			<select name="kind">
				<option value="creole">Разметка</option>
				<option value="tinymce" selected>Редактор</option>
			</select>
			<input type="submit" value="Добавить запись" />
		</form>
	</div>
	</td></tr></table>
EOT;
}

# Подсчёт числа записей
function get_total_record_count($groupid, $mode) {
	global $dbtableprefix;
	
	$query = "SELECT COUNT(id) FROM {$dbtableprefix}records " .
		"WHERE " .
		(($mode == 'tasks') ? "star > 0 AND star < 10" : "star = 0") .
		($groupid ? " AND groupid=$groupid" : "");
	$r = mysql_query($query);
	$row = mysql_fetch_row($r);
	$totalcount = $row[0];
	
	return $totalcount;
}

# Получение строки запроса для вывода списка записей
function get_record_list_query($groupid, $mode, $start, $limit) {
	global $dbtableprefix;
	
	$query = "SELECT id,title,star FROM {$dbtableprefix}records " . 
			"WHERE " .
			(($mode == 'tasks') ? "star > 0 AND star < 10 " : "star = 0 ") .
			($groupid ? " AND groupid=$groupid" : "") .
			"ORDER BY " .
			(($mode == 'tasks') ? "star DESC, modified DESC " : "title ASC ") .
			"LIMIT $start,$limit";
	if ($mode == 'tasks') {
		$query = "SELECT id,title,star FROM {$dbtableprefix}records ".
				"WHERE star > 0 AND star<10";
		if ($groupid) {
			$query = $query . " AND groupid=$groupid";
		}
		$query = $query . " ORDER BY star DESC,modified DESC " .
				"LIMIT $start,$limit";
	} else {
		$query = "SELECT id,title,star FROM {$dbtableprefix}records WHERE star=0";
		if ($groupid) {
			$query = $query . " AND groupid=$groupid";
		}
		$query = $query . " ORDER BY title ASC " .
				"LIMIT $start,$limit";
	}
	
	return $query;
}

# Вывод низа страницы

function out_footer() {
	global $use_authorization;
	echo "<hr style='margin-top:20'/>
		<table width=100%><tr>
		<td valign=top>
			<a href='creole_cheat_sheet.html'>Разметка</a> 
			<a href='admin.php'>Администрирование</a> ";
	if ($use_authorization)
		echo "<a href='login.php?logout'>Выйти</a>";
	echo "</td>
		<td align=right>WikiOne 2012<br/><img src='olympicmovement.png'/></td>
		</tr></table>";
}

?>