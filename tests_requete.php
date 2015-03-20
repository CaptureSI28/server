<?php

//connection BDD

$dbhost = 'localhost';
$dbname = 'si28';
$dbuser = 'root'; 
$dbpass = '';

try {
	$bdd = new PDO('mysql:host=' . $dbhost . ';dbname=' . $dbname . ';charset=utf8', $dbuser, $dbpass);
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

//ajout d'un flash dans la BDD

$joueur = $_POST['login'];
$qrcode = $_POST['data'];

try {
	$req = $bdd->prepare('INSERT INTO flasher (date_flash, joueur, qrcode) VALUES (NOW(), :joueur, :qrcode)');
	$req->execute(array(
		'joueur' => $joueur,
		'qrcode' => $qrcode
	));
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}
?>