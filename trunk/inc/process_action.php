<?php
# Авторизация
if($use_authorization && !isset($_SESSION['login'])) {
	echo "Error. No authorization.";
	return;
}
if(isset($_GET['action'])) {
	$action=$_GET['action'];
	switch($action) {
	case 'addgroup':
		$newgroupname=$_POST['name'];
		if($newgroupname) {
			if(!mysql_query("INSERT INTO {$dbtableprefix}groups SET name='".
				mysql_real_escape_string($newgroupname)."'"))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			echo "Группа $newgroupname добавлена.<br>";
			$r=mysql_query("SELECT LAST_INSERT_ID() FROM {$dbtableprefix}groups");
			if(!$r || !mysql_num_rows($r))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			$row=mysql_fetch_row($r);
			$groupid=$row[0];
			$groupname=$newgroupname;
		}
		else {
			echo "Имя группы не задано!<br>";
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
			echo "Группа переименована.<br>";
			$groupname=$newgroupname;
		}
		else { # Удаление
			$r=mysql_query("SELECT id FROM {$dbtableprefix}records
				WHERE groupid=$groupid");
			if(!$r)
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			if(mysql_num_rows($r)) {
				echo "Удалить можно тольку пустую группу!<br>";
			}
			else {
				if(!mysql_query("DELETE FROM {$dbtableprefix}groups 
						WHERE id=$groupid"))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				echo "Группа удалена<br>";
				$groupid=0;
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
			echo "Запись добавлена.<br>";
			$r=mysql_query("SELECT LAST_INSERT_ID() FROM {$dbtableprefix}records");
			if(!$r || !mysql_num_rows($r))
				{ echo "Error: ".mysql_error(); mysql_close(); return; }
			$row=mysql_fetch_row($r);
			$recordid=$row[0];
		}
		else {
			echo "Заголовок записи не задан!<br>";
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
			echo "Запись изменена.<br>";
		}
		else { # Удаление
			if($_POST['text'])
				echo "Удалить можно тольку запись без текста!<br>";
			else {
				if(!mysql_query("DELETE FROM {$dbtableprefix}records
						WHERE id=$recordid"))
					{ echo "Error: ".mysql_error(); mysql_close(); return; }
				echo "Запись удалена<br>";
				$recordid=0;
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
			echo "Комментарий добавлен.<br>";
		}
		else {
			echo "Текст комментарий не задан!<br>";
		}
		break;
	}
}
else
	$action='';
?>