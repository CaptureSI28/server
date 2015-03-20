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

//récupération du nombre de points total d'une équipe

try {
	$req = $bdd->prepare("
		SELECT COUNT(qrcode) as nbQrcode
		FROM flasher,joueur
		WHERE flasher.joueur=joueur.login_joueur 
		AND joueur.equipe=:equipe");
	$req->execute(array(
		'equipe' => $equipe
	));
	if($req->rowCount()>=1){
		$ligne=$req->fetch();
		echo $ligne['nbQrcode'];
	} 
	else echo('no result');
	} 
catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

//récupération du nombre de points d'une équipe pour une zone donnée

try {
	$req = $bdd->prepare("
		SELECT COUNT(qrcode) as nbQrcode
		FROM flasher,joueur,qrcode
		WHERE flasher.joueur=joueur.login_joueur 
		AND joueur.equipe=:equipe
		AND qrcode.zone=:zone
		AND flasher.qrcode=qrcode.id_qrcode");
	$req->execute(array(
		'equipe' => $equipe,
		'zone' => $zone
	));
	if($req->rowCount()>=1){
		$ligne=$req->fetch();
		echo $ligne['nbQrcode'];
	} 
	else echo('no result');
	} 
catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

?>