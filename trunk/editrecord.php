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
if (isset($_GET['recordid'])) {
	$recordid = $_GET['recordid'];
} else {
	echo "Не передан идентификатор записи.";
	return;
}
$record = read_record($recordid);
if (!$record) {
	echo "Error: no record with id=$recordid";
	return;
}

# Выводим страницу
out_html_header($title);

?>

<?php if ($record["kind"] == "tinymce") { ?>
<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
	tinyMCE.init({
		// General options
		mode : "exact",
		elements : "edit_record_text",
		plugins : "fullscreen",
		theme_advanced_buttons2_add : "fullscreen"
	});
	function clearForm() {
		document.getElementById("edit_record_title").value="";
		tinyMCE.get("edit_record_text").setContent("");
	}
</script>
<?php } else { ?>
<script type="text/javascript">
	function clearForm() {
		document.getElementById("edit_record_title").value="";
		document.getElementById("edit_record_text").value="";
	}
</script>
<?php } /* if */ ?>

<div class="pagetitle">
	<a href="index.php"><?php echo $title; ?></a> :: Редактирование записи
</div>

<form action='action.php?action=editrecord&recordid=<?php echo $record["id"]; ?>' 
	  method='POST'>
	<table class="form_table">
		<tr>
			<td class="form_left">Заголовок</td>
			<td class="form_right">
				<input name='title' id="edit_record_title" type='text' size=50 value=
					   '<?php echo $record["title"]; ?>'></input>
			</td>
		</tr>
		<tr>
			<td class="form_left">Приоритет</td>
			<td class="form_right">
				<select name='star'>
				<?php echo "
					<option value=0 " . ($record["star"] == 0 ? 'selected' : '') .
						"> </option>
					<option value=1 " . ($record["star"] == 1 ? 'selected' : '') .
						">*</option>
					<option value=2 " . ($record["star"] == 2 ? 'selected' : '') .
						">**</option>
					<option value=3 " . ($record["star"] == 3 ? 'selected' : '') .
						">***</option>";
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="form_left">Группа</td>
			<td class="form_right">
				<select name='groupid'>
				<?php
					echo "<option value=0 " . (!$record["groupid"] ? 'selected' : '') . " />";
					$r2 = mysql_query("SELECT id,name FROM {$dbtableprefix}groups");
					while ($row2 = mysql_fetch_row($r2)) {
						echo "<option value={$row2[0]} " .
							($record["groupid"] == $row2[0] ? 'selected' : '') . ">
							" . stripslashes($row2[1]) . "</option>";
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="form_right" colspan="2">
				<textarea name='text' id='edit_record_text' cols=70 rows=20
					style='margin-top:7px;margin-bottom:7px'
					><?php echo $record["text"]; ?></textarea>
			</td>
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

<?php if ($record['kind'] == 'creole') { ?>
<p><a href="creole_cheat_sheet.html">Справка по разметке</a></p>
<?php } /* if */ ?>

</body>
</html>