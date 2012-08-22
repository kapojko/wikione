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
					pvalue='" . mysql_real_escape_string($newmaxindexnotes) . "'
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
out_html_header("WikiOne {$settings['version']}");
if ($message) {
	echo $message . "<br>";
}
# Настройки
?>
<div class="pagetitle">
	<a href="index.php"><?php echo $title; ?></a> :: Настройки
</div>
<form action='admin.php?action=editsettings' method='POST'>
	<table class="form_table">
		<tr>
			<td class="form_left">Заголовок</td>
			<td class="form_right"><input name='title' type='text' value=
			'<?php echo $title; ?>'></input></td>
		</tr>
		<tr>
			<td class="form_left">Количество записей в списке</td>
			<td class="form_right"><input name='maxindexnotes' type='text' value=
			'<?php echo $maxindexnotes; ?>'></input></td>
		</tr>
		<tr>
			<td class="form_final" colspan="2">
				<input type='submit' value='Сохранить изменения'></input>
			</td>
		</tr>
	</table>
</form>
</body>
</html>