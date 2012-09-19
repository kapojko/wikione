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
	$groupid = 0;
}

# Выводим страницу
out_html_header($title);

?>

<div class="pagetitle">
	<a href="index.php"><?php echo $title; ?></a> :: Добавление записи
</div>

<form action='action.php?action=addrecord' method='POST'>
	<table class="form_table">
		<tr>
			<td class="form_left">Заголовок</td>
			<td class="form_right">
				<input name='title' id="edit_record_title" type='text' size=50 value=
					   '<?php echo $record["title"]; ?>'></input>
			</td>
		</tr>
		<tr>
			<td class="form_left">Группа</td>
			<td class="form_right">
				<select name='groupid'>
				<?php
					echo "<option value=0 " . (!$groupid ? 'selected' : '') . " />";
					$r2 = mysql_query("SELECT id,name FROM {$dbtableprefix}groups");
					while ($row2 = mysql_fetch_row($r2)) {
						echo "<option value={$row2[0]} " .
							($groupid == $row2[0] ? 'selected' : '') . ">
							" . stripslashes($row2[1]) . "</option>";
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="form_left">Приоритет</td>
			<td class="form_right">
				<select name='star'>
					<option value=0 selected> </option>
					<option value=1>*</option>
					<option value=2>**</option>
					<option value=3>***</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="form_left">Способ редактирования</td>
			<td>
				<select name="kind">
					<option value="creole" <?php if ($settings['default_kind'] == 'creole')
						echo 'selected'; ?> >Разметка (Creole)</option>
					<option value="textile"  <?php if ($settings['default_kind'] == 'textile')
						echo 'selected'; ?> >Разметка (Textile)</option>
					<option value="tinymce" <?php if ($settings['default_kind'] == 'tinymce')
						echo 'selected'; ?> >Визуальный редактор</option>
				</select>
			</td>
			<td class="form_comment">
				Способ редактирования определяет тип записи и не может быть
				изменён впоследствии
			</td>
		</tr>
		<tr>
			<td class="form_final">
				<input type='submit' value='Добавить запись'></input>
			</td>
		</tr>
	</table>
</form>

</body>
</html>