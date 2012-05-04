<?php 
	include('config.php');
	if($pwd)
		session_start();
?>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='icon' type='image/png' href='chess-knight.png' />
	<title><?php echo "$title"; ?></title>
	<script language='JavaScript'>
	// set cookie for given period (in seconds)
	function setCookie(name, value, period) {
		var expDate= new Date(); // current date
		expDate.setTime(expDate.getTime()+period*1000);
		document.cookie= name+"="+escape(value)+
				"; expires="+expDate.toGMTString();
	}
	// get cookie (returns NULL, if there isn't such cookie)
	function getCookie(name) {
		var prefix = name + "=";
		var cookieStartIndex = document.cookie.indexOf(prefix);
		if (cookieStartIndex == -1)
			return null;
		var cookieEndIndex = document.cookie.indexOf(";",
				cookieStartIndex + prefix.length);
		if (cookieEndIndex == -1)
			cookieEndIndex = document.cookie.length;
		return unescape(document.cookie.substring(cookieStartIndex +
				prefix.length, cookieEndIndex));
	}
	</script>
</head>
<body>
<?php
	# Обрабатываем сессии, если надо
	if($pwd) {
		if(!isset($_SESSION['login'])) {
			if(isset($_POST['password'])) {
				if($_POST['password'] == $pwd) {
					# Авторизация
					$_SESSION['login']=true;
					echo "<script>
						setCookie('pwd', '$pwd', 60*60*24*30); // 30 days
						</script>";
				}
				else {
					# Неверный пароль#
					echo "Неверный пароль.<br />
						<form action='index.php' method='POST'>
							<label>Введите пароль: </label>
							<input type=password name='password' />
							<input type=submit value='Ввести' />
						</form>
						</body>
						</html>";
				return;
				}
			}
			else {
				echo "<form id='loginForm' action='index.php' method='POST'>
						<label>Введите пароль: </label>
						<input id='nameInput' type=password name='password' />
						<input type=submit value='Ввести' />
					</form>
					<script>
					var cookiePwd= getCookie('pwd');
					if(cookiePwd) {
						document.getElementById('nameInput').value= cookiePwd;
						document.forms['loginForm'].submit();
					}
					</script>
					</body>
					</html>";
				return;
			}
		}
		if(isset($_GET['logout'])) {
			unset($_SESSION['login']);
			echo "<script>
					setCookie('pwd', '', -1); // delete a cookie
					window.location.href='index.php';
				</script>
				</body>
				</html>";
			return;
		}
	}
	
	include('wikirender.php');
	# Подключаем базу данных
	if(!mysql_connect($dbhost,$dbuser,$dbpwd))
		{ echo "Error connecting DB: ".mysql_error(); return; }
	mysql_query('SET NAMES utf8');
	if(!mysql_select_db($dbname)) { # база данных не существует
		$query="CREATE DATABASE $dbname CHARACTER SET utf8";
		if(!mysql_query($query))
			{ echo "Error creating DB: ".mysql_error(); mysql_close(); return; }
		if(!mysql_select_db($dbname))
			{ echo "Error selecting DB".mysql_error(); mysql_close(); return; }
	}
	$query="CREATE TABLE IF NOT EXISTS groups (
		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		name CHAR(30) NOT NULL
	)";
	if(!mysql_query($query))
		{ echo "Error creating table: ".mysql_error(); mysql_close(); return; }
	$query="CREATE TABLE IF NOT EXISTS  records (
		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		groupid INT,
		title CHAR(100) NOT NULL,
		star TINYINT,
		text TEXT
	)";
	if(!mysql_query($query))
		{ echo "Error creating table: ".mysql_error(); mysql_close(); return; }
	# Читаем параметры обзора
	if(isset($_GET['groupid'])) {
		$groupid=$_GET['groupid'];
		$r=mysql_query("SELECT name FROM groups WHERE id=$groupid");
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
	if(isset($_GET['action'])) {
		$action=$_GET['action'];
		# Обрабатываем запрошенные действия
		switch($action) {
		case 'addgroup':
			$newgroupname=$_POST['name'];
			if($newgroupname) {
				if(!mysql_query("INSERT INTO groups SET name='".
					mysql_real_escape_string($newgroupname)."'"))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				echo "Группа $newgroupname добавлена.\n";
				$r=mysql_query("SELECT LAST_INSERT_ID() FROM groups");
				if(!$r || !mysql_num_rows($r))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$row=mysql_fetch_row($r);
				$groupid=$row[0];
				$groupname=$newgroupname;
			}
			else {
				echo "Имя группы не задано!\n";
			}
			break;
		case 'editgroup':
			if(!$groupid)
				{ echo "Error: group id is not given"; mysql_close(); return; }
			$newgroupname=$_POST['name'];
			if($newgroupname) { # переименование
				if(!mysql_query("UPDATE groups SET name='".
					mysql_real_escape_string($newgroupname)."' 
					WHERE id=$groupid"))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				echo "Группа переименована.\n";
				$groupname=$newgroupname;
			}
			else { # Удаление
				$r=mysql_query("SELECT id FROM records WHERE groupid=$groupid");
				if(!$r)
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				if(mysql_num_rows($r))
					echo "Удалить можно тольку пустую группу!\n";
				else {
					if(!mysql_query("DELETE FROM groups WHERE id=$groupid"))
						{ echo "Error: ".mysql_error(); mysql_close(); return; }
					echo "Группа удалена\n";
					$groupid=0;
				}
			}
			break;
		case 'addrecord':
			$newrecordtitle=$_POST['title'];
			if($newrecordtitle) {
				$newrecordstar=$_POST['star'];
				$query="INSERT INTO records SET
					title='".mysql_real_escape_string($newrecordtitle)."',
					star='$newrecordstar'";
				if($groupid)
					$query=$query.",groupid=$groupid";
				if(!mysql_query($query))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				echo "Запись добавлена.\n";
				$r=mysql_query("SELECT LAST_INSERT_ID() FROM records");
				if(!$r || !mysql_num_rows($r))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$row=mysql_fetch_row($r);
				$recordid=$row[0];
			}
			else {
				echo "Заголовок записи не задан!\n";
			}
			break;
		case 'editrecord':
			if(!$recordid)
				{ echo "Error: record id is not given"; mysql_close(); return; }
			$newrecordtitle=$_POST['title'];
			if($newrecordtitle) { # изменение
				$query="UPDATE records SET groupid='{$_POST['groupid']}',
					title='".mysql_real_escape_string($newrecordtitle)."',
					star='{$_POST['star']}',
					text='".mysql_real_escape_string($_POST['text'])."'
					WHERE id=$recordid";
				if(!mysql_query($query))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				echo "Запись изменена.\n";
			}
			else { # Удаление
				if($_POST['text'])
					echo "Удалить можно тольку запись без текста!\n";
				else {
					if(!mysql_query("DELETE FROM records WHERE id=$recordid"))
						{ echo "Error: ".mysql_error(); mysql_close(); return; }
					echo "Запись удалена\n";
					$recordid=0;
				}
			}
			break;
		}
	}
	else
		$action='';
	
	# Выводим страницу
	# Заголовок
	echo "<table><tr><td>
		<a href='index.php'><img src='chess-knight.png' /></a></td>
		<td><h1>$title</h1></td></tr></table>";
	# Список групп
	echo "<table width=100% bgcolor=#FFD4FF><tr><td>";
	$r=mysql_query("SELECT id,name FROM groups");
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }		
	while($row=mysql_fetch_array($r))
		echo "<a href='index.php?groupid={$row['id']}'>{$row['name']}</a> ";
	echo "</td><td align=right>
		<form action='index.php?action=addgroup' method='POST'>
			<input name='name' type='text'/>
			<input type='submit' value='Добавить группу'/>
		</form></td></tr></table>";
	# Список записей
	if($recordid)
		echo "<table width=100%><tr><td bgcolor=#D4FFD4 width=300px valign=top>";
	else
		echo "<table align=center><tr><td bgcolor=#D4FFD4>";
	echo "<h3>";
	if($groupid) {
		echo $groupname;
		$query="SELECT id,title,star FROM records WHERE groupid=$groupid";
	}
	else {
		echo "Все записи";
		$query="SELECT id,title,star FROM records";
	}
	$query=$query." ORDER BY star DESC";
	$r=mysql_query($query);
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }		
	echo "</h3>
		<a href=#addrecord><p align='right' style='margin-bottom:7px'>&darr;Добавить запись</p></a>
		<ol>";
	while($row=mysql_fetch_array($r)) {
		echo "<li>";
		if($row['star'] == 3)
			echo "<b>";
		echo "<a href='index.php?".($groupid ? "groupid=$groupid&" : "").
			"recordid={$row['id']}'>{$row['title']}";
		if($row['star'])
			echo "<sup>".str_repeat('*',$row['star'])."</sup></b>";
		echo "</a></li>\n";
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
		</form>";
	if($groupid) {
		# Редактирование группы
		echo "<div align=right>
			<a href='#editgroup'
			onclick=\"javascript:document.getElementById('editgroup').
			style.display='block'\">
			Изменить группу</a></div>\n
			<div id='editgroup' style='display:none'>
			<a name='editgroup' />
			<form action='index.php?action=editgroup&groupid=$groupid'
			method='POST'>
				<input name='name' type='text' value='$groupname'/>
				<input type='submit' value='Сохранить' />
			</form></div>";
	}
	echo "</td>";
	# Текущая запись
	if($recordid) {
		echo "<td bgcolor=#FFFFD4 valign=top>";
		$r=mysql_query("SELECT * FROM records WHERE id=$recordid");
		if(!$r)
			{ echo "Error: ".mysql_error(); mysql_close(); return; }
		if(!mysql_num_rows($r))
			{ echo "Error: no record with id=$recordid"; mysql_close(); return; }
		$row=mysql_fetch_array($r);
		echo "<h2>";
		$groupid1=$row['groupid'];
		if($groupid1) {
			$r1=mysql_query("SELECT name FROM groups WHERE id=$groupid1");
			if(!$r1)
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			if(!mysql_num_rows($r))
				{ echo "Error: unexisted group id ($groupid1)."; mysql_close(); return; }
			$row1=mysql_fetch_row($r1);
			$groupname1=$row1[0];
			echo "$groupname1: ";
		}
		else
			$groupname1='';
		echo $row['title'];
		$star1=$row['star'];
		if($star1)
			echo "<sup>".str_repeat('*',$star1)."</sup>";
		echo "</h2>\n";
		echo wiki_render($row['text']);
		# Редактирование
		echo "<div align=right>
			<a href='#editrecord'
			onclick=\"javascript:document.getElementById('editrecord').
			style.display='block'\">
			Редактировать</a></div>\n
			<div id='editrecord' style='display:".
			($action == 'addrecord' ? 'block' : 'none')."'>
			<a name='editrecord' />
			<form action='index.php?action=editrecord&recordid=$recordid".
			($groupid ? "&groupid=$groupid" : "")."' method='POST'>
			<input name='title' type='text' size=70 value='{$row['title']}' />
			<select name='star'/>
				<option value=0 ".($star1 == 0 ? 'selected' : '')." />
				<option value=1 ".($star1 == 1 ? 'selected' : '').">*</option>
				<option value=2 ".($star1 == 2 ? 'selected' : '').">**</option>
				<option value=3 ".($star1 == 3 ? 'selected' : '').">***</option>
			</select>
			<select name='groupid' />
				<option value=0 ".(!$groupid1 ? 'selected' : '')." />";
		$r2=mysql_query("SELECT id,name FROM groups");
		if(!$r2)
			{ echo "Error: ".mysql_error(); mysql_close(); return; }
		while($row2=mysql_fetch_array($r2))
			echo "<option value={$row2['id']} ".($groupid1 == $row2['id'] ? 'selected' : '').">
				{$row2['name']}</option>\n";
		echo "</select><br/>
			<textarea name='text' cols=60 rows=10 style='margin-top:7px;margin-bottom:7px'>";
		echo $row['text'];
		echo "</textarea><br />
			<input type='submit' value='Сохранить' />
			</form></div>
			</td>";
	}
	echo "</tr></table>";
	# Низ страницы
	echo "<hr/>
		<table width=100%><tr>
		<td valign=top>
			<a href='syntax.html'>Разметка</a> ";
	if($pwd)
		echo "<a href='index.php?logout'>Выйти</a>";
	echo "</td>
		<td align=right>WikiOne 2012<br/><img src='olympicmovement.png'/></td>
		</tr></table>";
	# Готово
	mysql_close();
?>
</body>
</html>