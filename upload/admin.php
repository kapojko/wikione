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
			mysql_query("UPDATE {$dbtableprefix}settings SET
					pvalue='" . mysql_real_escape_string($_POST['title']) . "'
					WHERE pkey='title'");
			mysql_query("UPDATE {$dbtableprefix}settings SET
					pvalue='" . mysql_real_escape_string($_POST['maxindexnotes']) . "'
					WHERE pkey='maxindexnotes'");
			mysql_query("UPDATE {$dbtableprefix}settings SET
					pvalue='" . mysql_real_escape_string($_POST['default_kind']) . "'
					WHERE pkey='default_kind'");
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
			<td class="form_left">Способ редактирования по умолчанию</td>
			<td class="form_right">
				<select name='default_kind'>
					<option value="creole" <?php if ($settings['default_kind'] == 'creole')
						echo 'selected'; ?> >Разметка (Creole)</option>
					<option value="textile"  <?php if ($settings['default_kind'] == 'textile')
						echo 'selected'; ?> >Разметка (Textile)</option>
					<option value="tinymce" <?php if ($settings['default_kind'] == 'tinymce')
						echo 'selected'; ?> >Визуальный редактор</option>
				</select>
			</td>
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