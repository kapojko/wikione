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
</head>
<body>
<?php	
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
	}
	else {
		require 'openid.php';
		try {
			$lopenid = new LightOpenID($domain);
			if(!$lopenid->mode) {
				# Начало авторизации
				$lopenid->identity = $openid;
				# The following two lines request email, full name, and a nickname
				# from the provider. Remove them if you don't need that data.
				#$lopenid->required = array('contact/email');
				#$lopenid->optional = array('namePerson', 'namePerson/friendly');
				#header('Location: ' . $lopenid->authUrl());
				echo "<script>
					window.location.href='".$lopenid->authUrl()."'
					</script></body></html>";
				return;
			} elseif($lopenid->mode == 'cancel') {
				echo 'Authorization canceled.';
			}
			else {
				# Авторизация выполнена
				$_SESSION['login']=true;
				echo "<script>
					window.location.href='index.php';
					</script></body></html>";
				return;
			}
		} catch(ErrorException $e) {
			echo $e->getMessage();
		}
	}
?>
</body>
</html>