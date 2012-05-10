<?php 
	include('config.php');
	if($use_authorization)
		session_start();
?>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='icon' type='image/png' href='chess-knight.png' />
	<title><?php echo "$title"; ?></title>
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
	# Выполнение действий
	if(isset($_GET['action'])) {
		$action=$_GET['action'];
		switch($action) {
		case 'editsettings':
			$newtitle=$_POST['title'];
			if(!mysql_query("UPDATE {$dbtableprefix}settings SET
					title='".mysql_real_escape_string($newtitle)."'"))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			echo "Изменения сохранены.<br>";
			break;
		}
	}

	#Вывод
	# Настройки
	$r=mysql_query("SELECT title FROM {$dbtableprefix}settings");
	if(!$r or !($row= mysql_fetch_row($r))) {
		echo "Error reading settings from DB".mysql_error(); mysql_close(); return;
	}
	echo "<h3>Настройки</h3>
		<form action='admin.php?action=editsettings' method='POST'>
		Заголовок: <input name='title' type='text' value='".
		stripslashes($row[0])."'></input><br>
		<input type='submit' value='Сохранить изменения'></input>
		</form>";
	# Ссылка на главную страницу
	echo "<p><a href='index.php'>На главную страницу</a>";
?>
</body>
</html>