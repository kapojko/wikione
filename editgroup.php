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

# Читаем параметры текущего вида
if (isset($_GET['groupid'])) {
	$groupid = $_GET['groupid'];
} else {
	echo "Не передан идентификатор группы.";
	return;
}
$group = read_group($groupid);
if (!$group) {
	echo "Error: no group with id=$groupid";
	return;
}

# Выводим страницу
out_html_header($title);

?>

<script type="text/javascript">
	function clearForm() {
		document.getElementById("edit_group_name").value="";
	}
</script>

<div class="pagetitle">
	<a href="index.php"><?php echo $title; ?></a> :: Редактирование группы
</div>

<form action='action.php?action=editgroup&groupid=<?php echo $group["id"]; ?>' 
	  method='POST'>
	<table class="form_table">
		<tr>
			<td class="form_left">Название</td>
			<td class="form_right">
				<input name='name' id="edit_group_name" type='text' size=30 value=
					   '<?php echo $group["name"]; ?>'></input>
			</td>
		</tr>
		<tr>
			<td class="form_left">Порядок</td>
			<td class="form_right">
				<input name="order" type="text" value="<?php echo $group["order"]; ?>">
				</input>
			</td>
			<td class="form_comment">Список групп выводится по возрастанию этого числа</td>
		</tr>
		<tr>
			<td style="text-align: left">
				<span class='pseudolink' onclick='clearForm()'>Очистить</span>
			</td>
			<td class="form_final">
				<input type='submit' value='Сохранить'></input>
			</td>
		</tr>
	</table>
</form>

</body>
</html>
