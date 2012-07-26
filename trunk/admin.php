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

# Выполнение действий
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	switch ($action) {
		case 'editsettings':
			$newtitle = $_POST['title'];
			if (!mysql_query("UPDATE {$dbtableprefix}settings SET
					pvalue='" . mysql_real_escape_string($newtitle) . "'
					WHERE pkey='title'")) {
				echo "Error: " . mysql_error();
				return;
			}
			$newmaxindexnotes= $_POST['maxindexnotes'];
			if (!mysql_query("UPDATE {$dbtableprefix}settings SET
					pvalue='" . mysql_real_escape_string($maxindexnotes) . "'
					WHERE pkey='maxindexnotes'")) {
				echo "Error: " . mysql_error();
				return;
			}
			$message = "Изменения сохранены.";
			break;
	}
}
# Читаем настройки
$settings = load_settings($dbtableprefix);
$title = $settings['title'];
$maxindexnotes = $settings['maxindexnotes'];

#Вывод
out_html_header("WikiOne $wikione_version");
if ($message) {
	echo $message . "<br>";
}
# Настройки
?>
<h3>Настройки</h3>
<form action='admin.php?action=editsettings' method='POST'>
	Заголовок: <input name='title' type='text' value=
		'<?php echo $title; ?>'></input><br>
	Количество записей на главной странице:
	<input name='maxindexnotes' type='text' value=
		'<?php echo $maxindexnotes; ?>'></input><br>
	<input type='submit' value='Сохранить изменения'></input>
</form>
<p><a href='index.php'>На главную страницу</a>
</body>
</html>