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
if (isset($_GET['noteid'])) {
	$noteid = $_GET['noteid'];
} else {
	echo "Не передан идентификатор комментария.";
	return;
}
$note = read_note($noteid);
if (!$note) {
	echo "Error: no note with id=$noteid";
	return;
}

# Выводим страницу
out_html_header($title);

?>

<script type="text/javascript">
	function clearForm() {
		document.getElementById("edit_note_text").value="";
	}
</script>

<div class="pagetitle">
	<a href="index.php"><?php echo $title; ?></a> :: Редактирование комментария
</div>

<form action='action.php?action=editnote&noteid=<?php echo $note["id"]; ?>' 
	  method='POST'>
	<table class="form_table">
		<tr>
			<td class="form_right" colspan="2">
				<textarea name='text' id='edit_note_text' cols=70 rows=5
					style='margin-top:7px;margin-bottom:7px'
					><?php echo $note["text"]; ?></textarea>
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

</body>
</html>
