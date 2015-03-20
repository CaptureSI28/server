<?php

//insertion nouvelle partie dans la table PARTIE

try {
	$req = $bdd->prepare('INSERT INTO partie (date_debut, date_fin) VALUES (:date_debut, :date_fin)');
	$req->execute(array(
		'date_debut' => $date_debut,
		'date_fin' => $date_fin
	));
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

//insertion nouveau joueur dans la table EQUIPE

try {
	$req = $bdd->prepare('INSERT INTO equipe (partie, equipe) VALUES (:partie, :equipe)');
	$req->execute(array(
		'partie' => $partie,
		'equipe' => $equipe
	));
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

//insertion nouveau joueur dans la table JOUEURS

try {
	$req = $bdd->prepare('INSERT INTO joueur (login_joueur, equipe) VALUES (:joueur, :equipe)');
	$req->execute(array(
		'joueur' => $joueur,
		'equipe' => $equipe
	));
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

//insertion données dans la table FLASHER

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