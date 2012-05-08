<?php 
	include('config.php');
	if($use_authorization)
		session_start();
?>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='icon' type='image/png' href='chess-knight.png' />
	<title><?php echo "$title"; ?>: вход</title>
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
	# Подключение к БД
	if(!mysql_connect($dbhost,$dbuser,$dbpwd))
		{ echo "Error connecting DB: ".mysql_error(); return; }
	mysql_query("SET NAMES utf8");
	if(!mysql_select_db($dbname))
		{ echo "Error selecting DB: ".mysql_error(); mysql_close(); return; }
	if(isset($_SESSION['login'])) {
		# Авторизация уже выполнена
		if(!isset($_GET['logout'])) {
			# Авторизация не требуется, переходим на основную страницу
			# Переходим на основную страницу
			echo "<script> window.location.href='index.php';
				</script></body></html>";
			return;
		}
		# Выход из авторизации
		unset($_SESSION['login']);
		echo
			"<script>
				setCookie('pwd', '', -1); // delete a cookie
			</script>";
	}
	else if(isset($_POST['password'])) {
		# Попытка авторизации
		$r=mysql_query("SELECT passwordcrypt FROM {$dbtableprefix}settings");
		if(!$r || !mysql_num_rows($r)) {
			echo "Error reading password from DB: ".mysql_error();
			mysql_close(); return;
		}
		$row=mysql_fetch_row($r);
		$passwordcrypt=$row[0];
		if(crypt($_POST['password'],$passwordcrypt) == $passwordcrypt) {
			# Авторизация
			$_SESSION['login']=true;
			echo "<script>
				if(!getCookie('pwd')) {
					setCookie('pwd', '$pwd', 60*60*24*30); // 30 days
				}
				window.location.href='index.php';
				</script></body></html>";
			return;
		}
		# Неверный пароль#
		echo "<script>
			setCookie('pwd', '', -1); // delete a cookie to avoid recursion
			</script>
			Неверный пароль.<br>";
	}
?>
	<form action='login.php' method='POST'>
		<label>Введите пароль: </label>
		<input type=password name='password' />
		<input type=submit value='Ввести' />
	</form>
	<!-- Автоматическая авторизация по cookie. -->
	<script>
		var cookiePwd= getCookie('pwd');
		if(cookiePwd) {
			document.getElementById('nameInput').value= cookiePwd;
			document.forms['loginForm'].submit();
		}
	</script>
	</body>
	</html>
</body>
</html>