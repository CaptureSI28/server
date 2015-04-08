<?php
	date_default_timezone_set('Europe/Paris');
	require_once('db_constants.php');
	$bdd = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASSWORD);
	$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$bdd->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
?>