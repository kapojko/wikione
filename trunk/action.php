<?php 
	include('config.php');
	if($use_authorization)
		session_start();
?>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='icon' type='image/png' href='chess-knight.png' />
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>WikiOne <?php echo $wikione_version; ?></title>
</head>
<body>
	<?php
	include('common.php');
	if(isset($_GET['action'])) {
		# Код установит переменные $result, $message и $location
		$action=$_GET['action'];
		switch($action) {
		case 'addgroup':
			$newgroupname=$_POST['name'];
			if($newgroupname) {
				if(!mysql_query("INSERT INTO {$dbtableprefix}groups SET name='".
					mysql_real_escape_string($newgroupname)."'"))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$r=mysql_query("SELECT LAST_INSERT_ID() FROM {$dbtableprefix}groups");
				if(!$r or !mysql_num_rows($r))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$row=mysql_fetch_row($r);
				$result=true;
				$message="Группа $newgroupname добавлена.";
				$location="index.php?groupid={$row[0]}";
			}
			else {
				$result=false;
				$message= "Имя группы не задано!";
				$location="index.php";
			}
			break;
		case 'editgroup':
			if(!$groupid)
				{ echo "Error: group id is not given"; mysql_close(); return; }
			$newgroupname=$_POST['name'];
			if($newgroupname) { # переименование
				if(!mysql_query("UPDATE {$dbtableprefix}groups SET name='".
						mysql_real_escape_string($newgroupname)."' 
						WHERE id=$groupid"))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$result=true;
				$message="Группа переименована.";
				$location="index.php?groupid=$groupid";
			}
			else { # Удаление
				$r=mysql_query("SELECT id FROM {$dbtableprefix}records
					WHERE groupid=$groupid");
				if(!$r)
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				if(mysql_num_rows($r)) {
					$result=false;
					$message="Удалить можно тольку пустую группу!";
					$location="index.php?groupid=$groupid";
				}
				else {
					if(!mysql_query("DELETE FROM {$dbtableprefix}groups 
							WHERE id=$groupid"))
						{ echo "Error: ".mysql_error(); mysql_close(); return; }
					$result=true;
					$message="Группа удалена";
					$location="index.php";
				}
			}
			break;
		case 'addrecord':
			$newrecordtitle=$_POST['title'];
			if($newrecordtitle) {
				$newrecordstar=$_POST['star'];
				$query="INSERT INTO {$dbtableprefix}records SET
					title='".mysql_real_escape_string($newrecordtitle)."',
					star='$newrecordstar',
					created=NOW()";
				if($groupid)
					$query=$query.",groupid=$groupid";
				if(!mysql_query($query))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$r=mysql_query("SELECT LAST_INSERT_ID() FROM {$dbtableprefix}records");
				if(!$r || !mysql_num_rows($r))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$row=mysql_fetch_row($r);
				$result=true;
				$message="Запись добавлена.";
				$location="record.php?recordid={$row[0]}";
				if($groupid)
					$location=$location."&groupid=$groupid";
			}
			else {
				$result=false;
				$message="Заголовок записи не задан!";
				$location="index.php";
				if($groupid)
					$location=$location."?groupid=$groupid";
			}
			break;
		case 'editrecord':
			if(!$recordid)
				{ echo "Error: record id is not given"; mysql_close(); return; }
			$newrecordtitle=$_POST['title'];
			if($newrecordtitle) { # изменение
				$query="UPDATE {$dbtableprefix}records SET groupid='{$_POST['groupid']}',
					title='".mysql_real_escape_string($newrecordtitle)."',
					star='{$_POST['star']}',
					text='".mysql_real_escape_string($_POST['text'])."',
					modified=NOW()
					WHERE id=$recordid";
				if(!mysql_query($query))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$result=true;
				$message="Запись изменена.";
				$location="record.php?recordid=$recordid";
				if($groupid)
					$location=$location."&groupid=$groupid";
			}
			else { # Удаление
				if($_POST['text']) {
					$result=true;
					$message="Удалить можно тольку запись без текста!";
					$location="record.php?recordid=$recordid";
					if($groupid)
						$location=$location."&groupid=$groupid";
				}
				else {
					if(!mysql_query("DELETE FROM {$dbtableprefix}records
							WHERE id=$recordid"))
						{ echo "Error: ".mysql_error(); mysql_close(); return; }
					$result=true;
					$message="Запись удалена";
					$location="index.php";
					if($groupid)
						$location=$location."?groupid=$groupid";				
				}
			}
			break;
		case 'addnote':
			if(!$recordid)
				{ echo "Error: record id is not given"; mysql_close(); return; }
			$notetext=$_POST['text'];
			if($notetext) {
				$query="INSERT INTO {$dbtableprefix}notes(recordid,text,created)
					VALUES('$recordid',
					'".mysql_real_escape_string($notetext)."',
					NOW())";
				if(!mysql_query($query))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				$result=true;
				$message="Комментарий добавлен.";
				$location="record.php?recordid=$recordid";
				if($groupid)
					$location=$location."&groupid=$groupid";
			}
			else {
				$result=false;
				$message="Текст комментария не задан!";
				$location="record.php?recordid=$recordid";
				if($groupid)
					$location=$location."&groupid=$groupid";
			}
			break;
		}
	}
	# Переходим на определённую страницу
	echo "<html>
		<body>
		<form id='submitform' action='$location' method='POST'>
			<input name='result' type='hidden' value='$result'/>
			<input name='message' type='hidden' value='$message'/>
		</form>
		<script>
			document.getElementById('submitform').submit();
		</script>";
	?>
</body>
</html>