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
# Читаем настройки
$settings = load_settings($dbtableprefix);
$title = $settings['title'];

# Выводим страницу
out_html_header($title);

?>

<div class="pagetitle">
	<a href="index.php"><?php echo $title; ?></a> :: Добавление группы
</div>

<form action='action.php?action=addgroup' method='POST'>
	<table class="form_table">
		<tr>
			<td class="form_left">Название</td>
			<td class="form_right">
				<input name='name' id="edit_group_name" type='text' size=30></input>
			</td>
		</tr>
		<tr>
			<td class="form_left">Порядок</td>
			<td class="form_right">
				<input name="order" type="text" value="0.0">
				</input>
			</td>
			<td class="form_comment">Список групп выводится по возрастанию этого числа</td>
		</tr>
		<tr>
			<td class="form_final">
				<input type='submit' value='Добавить группу'></input>
			</td>
		</tr>
	</table>
</form>

</body>
</html>