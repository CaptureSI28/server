<?php
	require_once('fonctions.php');
	$req = $bdd->query('
		SELECT NOW() AS time;
	');
	if ($row = $req->fetch()) {
		echo '<p>Time (via MySQL): ' . $row['time'] . '</p>';
	}
	echo '<p>Time (via MySQL): ' . date('Y-m-d H:i:s', time()) . '</p>';
?>
