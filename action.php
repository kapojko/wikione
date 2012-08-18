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
# Подключаем движок Вики
$creole = load_wiki_engine();

# Читаем параметры текущего вида
if (isset($_GET['groupid'])) {
	$groupid = $_GET['groupid'];
}
else
	$groupid = 0;
if (isset($_GET['recordid']))
	$recordid = $_GET['recordid'];
else
	$recordid = 0;
if (isset($_GET['noteid']))
	$noteid = $_GET['noteid'];
else
	$noteid = 0;

if (isset($_GET['action'])) {
	# Код установит переменные $result, $message и $location
	$action = $_GET['action'];
	switch ($action) {
		case 'addgroup':
			$newgroupname = $_POST['name'];
			$new_group_order = $_POST['order'];
			if ($newgroupname) {
				if (!mysql_query("INSERT INTO {$dbtableprefix}groups SET name='" .
						mysql_real_escape_string($newgroupname) . "',
						`order` = " . (real)$new_group_order)) {
					echo "Error: " . mysql_error();
					return;
				}
				$r = mysql_query("SELECT LAST_INSERT_ID() FROM {$dbtableprefix}groups");
				if (!$r or !mysql_num_rows($r)) {
					echo "Error: " . mysql_error();
					return;
				}
				$row = mysql_fetch_row($r);
				$result = true;
				$message = "Группа $newgroupname добавлена.";
				$location = "index.php?groupid={$row[0]}";
			} else {
				$result = false;
				$message = "Имя группы не задано!";
				$location = "index.php";
			}
			break;
		case 'editgroup':
			if (!$groupid) {
				echo "Error: group id is not given";
				return;
			}
			$newgroupname = $_POST['name'];
			$new_group_order = $_POST['order'];
			if ($newgroupname) { # переименование
				if (!mysql_query("UPDATE {$dbtableprefix}groups SET name='" .
						mysql_real_escape_string($newgroupname) . "',
						`order` = " . (real)$new_group_order . "
						WHERE id=$groupid")) {
					echo "Error: " . mysql_error();
					return;
				}
				$result = true;
				$message = "Группа изменена.";
				$location = "index.php?groupid=$groupid";
			} else { # Удаление
				$r = mysql_query("SELECT id FROM {$dbtableprefix}records
				WHERE groupid=$groupid");
				if (!$r) {
					echo "Error: " . mysql_error();
					return;
				}
				if (mysql_num_rows($r)) {
					$result = false;
					$message = "Удалить можно тольку пустую группу!";
					$location = "index.php?groupid=$groupid";
				} else {
					if (!mysql_query("DELETE FROM {$dbtableprefix}groups 
						WHERE id=$groupid")) {
						echo "Error: " . mysql_error();
						return;
					}
					$result = true;
					$message = "Группа удалена";
					$location = "index.php";
				}
			}
			break;
		case 'addrecord':
			$newrecordtitle = $_POST['title'];
			if ($newrecordtitle) {
				$newgroupid = $_POST['groupid'];
				$newrecordstar = $_POST['star'];
				$newrecordkind = $_POST['kind'];
				$query = "INSERT INTO {$dbtableprefix}records SET
						kind = '$newrecordkind',
						title='" . mysql_real_escape_string($newrecordtitle) . "',
						star='$newrecordstar',
						created=NOW()";
				if ($newgroupid)
					$query = $query . ",groupid=$newgroupid";
				if (!mysql_query($query)) {
					echo "Error: " . mysql_error();
					return;
				}
				$r = mysql_query("SELECT LAST_INSERT_ID() FROM {$dbtableprefix}records");
				if (!$r || !mysql_num_rows($r)) {
					echo "Error: " . mysql_error();
					return;
				}
				$row = mysql_fetch_row($r);
				$result = true;
				$message = "Запись добавлена.";
				$location = "record.php?recordid={$row[0]}";
			}
			else {
				$result = false;
				$message = "Заголовок записи не задан!";
				$location = "index.php";
				if ($groupid)
					$location = $location . "?groupid=$groupid";
			}
			break;
		case 'editrecord':
			if (!$recordid) {
				echo "Error: record id is not given";
				return;
			}
			$newrecordtitle = $_POST['title'];
			if ($newrecordtitle) { # изменение
				$query = "UPDATE {$dbtableprefix}records SET groupid='{$_POST['groupid']}',
				title='" . mysql_real_escape_string($newrecordtitle) . "',
				star='{$_POST['star']}',
				text='" . mysql_real_escape_string($_POST['text']) . "',
				modified=NOW()
				WHERE id=$recordid";
				if (!mysql_query($query)) {
					echo "Error: " . mysql_error();
					return;
				}
				$result = true;
				$message = "Запись изменена.";
				$location = "record.php?recordid=$recordid";
			}
			else { # Удаление
				if ($_POST['text']) {
					$result = true;
					$message = "Удалить можно тольку запись без текста!";
					$location = "record.php?recordid=$recordid";
				}
				else {
					if (!mysql_query("DELETE FROM {$dbtableprefix}records
						WHERE id=$recordid")) {
						echo "Error: " . mysql_error();
						return;
					}
					$result = true;
					$message = "Запись удалена";
					$location = "index.php";
					if ($groupid)
						$location = $location . "?groupid=$groupid";
				}
			}
			break;
		case 'addnote':
			if (!$recordid) {
				echo "Error: record id is not given";
				return;
			}
			$notetext = $_POST['text'];
			if ($notetext) {
				$query = "INSERT INTO {$dbtableprefix}notes(recordid,text,created)
				VALUES('$recordid',
				'" . mysql_real_escape_string($notetext) . "',
				NOW())";
				if (!mysql_query($query)) {
					echo "Error: " . mysql_error();
					return;
				}
				$result = true;
				$message = "Комментарий добавлен.";
				$location = "record.php?recordid=$recordid";
			}
			else {
				$result = false;
				$message = "Текст комментария не задан!";
				$location = "record.php?recordid=$recordid";
			}
			break;
		case 'editnote':
			if (!$noteid) {
				echo "Error: note id is not given";
				return;
			}
			$new_note_text = $_POST['text'];
			if ($new_note_text) { # изменение
				$query = "UPDATE {$dbtableprefix}notes SET
						text='" . mysql_real_escape_string($new_note_text) . "'
						WHERE id = $noteid";
				if (!mysql_query($query)) {
					echo "Error: " . mysql_error();
					return;
				}
				
				# читаем идентификатор записи для перехода
				$r = mysql_query("SELECT recordid FROM {$dbtableprefix}notes
						WHERE id = $noteid");
				$row = mysql_fetch_row($r);
				if (!$row) {
					echo "Error: unable to select note with given id";
					return;
				}
				$note_record_id = $row[0];
				
				$result = true;
				$message = "Комментарий изменён.";
				$location = "record.php?recordid=$note_record_id";
			}
			else { # Удаление
				# читаем идентификатор записи для перехода
				$r = mysql_query("SELECT recordid FROM {$dbtableprefix}notes
						WHERE id = $noteid");
				$row = mysql_fetch_row($r);
				if (!$row) {
					echo "Error: unable to select note with given id";
					return;
				}
				$note_record_id = $row[0];

				mysql_query("DELETE FROM {$dbtableprefix}notes
						WHERE id = $noteid");
				$result = true;
				$message = "Комментарий удалён";
				$location = "record.php?recordid=$note_record_id";
			}
			break;
	}
}

out_html_header($title);

?>
<form id='submitform' action='<?php echo $location; ?>' method='POST'>
	<input name='result' type='hidden' value='<?php echo $result; ?>'/>
	<input name='message' type='hidden' value='<?php echo $message; ?>'/>
</form>
<script>
	document.getElementById('submitform').submit();
</script>
</body>
</html>