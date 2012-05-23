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
	# Общие действия
	include('common.php');
	# Шапка
	out_header($groupid);
	# Список записей
	echo "<div class='indexrecordlist'>";
	$query="SELECT id,title,star FROM {$dbtableprefix}records WHERE star<10";
	if($groupid) {
		$query=$query." AND groupid=$groupid";
	}
	$query=$query." ORDER BY star DESC";
	$r=mysql_query($query);
	if(!$r)
		{ echo "Error: ".mysql_error(); mysql_close(); return; }		
	echo "<ol>";
	while($row=mysql_fetch_row($r)) {
		echo "<li><a href='record.php?".($groupid ? "groupid=$groupid&" : "").
			"recordid={$row[0]}'><span class='star{$row[2]}'>
			".stripslashes($row[1])."</span></a></li>";
	}
	echo "</ol></div>";
	# Низ страницы
	out_footer();
	# Готово
	mysql_close();
?>
</body>
</html>