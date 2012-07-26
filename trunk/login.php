<?php

include('config.php');
include('common.php');

if ($use_authorization)
	session_start();

# Подключение к БД
if (!connect_to_db($dbhost, $dbuser, $dbpwd, $dbname)) {
	return;
}
# Читаем настройки
$settings = load_settings($dbtableprefix);
$title = $settings['title'];
# Подключаем движок Вики
$creole = load_wiki_engine();

if (isset($_SESSION['login'])) {
	# Авторизация уже выполнена
	if (!isset($_GET['logout'])) {
		# Авторизация не требуется, переходим на основную страницу
		# Переходим на основную страницу
		header('Location: index.php');
		return;
	}
	# Выход из авторизации
	unset($_SESSION['login']);
	header('Location: login.php');
	return;
} else {
	require 'openid.php';
	try {
		$lopenid = new LightOpenID($domain);
		if (!$lopenid->mode) {
			# Начало авторизации
			$lopenid->identity = $openid;
			$lopenid->realm = 'http://' . $domain_punicode;
			$lopenid->returnUrl = $lopenid->realm . $_SERVER['REQUEST_URI'];
			# The following two lines request email, full name, and a nickname
			# from the provider. Remove them if you don't need that data.
			#$lopenid->required = array('contact/email');
			#$lopenid->optional = array('namePerson', 'namePerson/friendly');
			#header('Location: ' . $lopenid->authUrl());
			header('Location: ' . $lopenid->authUrl());
		} elseif ($lopenid->mode == 'cancel') {
			echo 'Authorization canceled.';
		} else {
			# Авторизация выполнена
			$_SESSION['login'] = true;
			header('Location: index.php');
			return;
		}
	} catch (ErrorException $e) {
		echo $e->getMessage();
	}
}
?>