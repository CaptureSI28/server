<?php
	session_start();

	require_once('fonctions.php');

	echo json_encode(validateCasTicket($_POST['ticket'], $_POST['service']));
?>
