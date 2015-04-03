<?php

//connection BDD

require_once('db_connect.php');

//insertion nouvelle zone dans la table ZONES

try {
	$req = $bdd->prepare('
		INSERT INTO zones () 
		VALUES ()');
	$req->execute(array(
	));
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

/*
//insertion nouveau qrcode dans la table QRCODES

try {
	$req = $bdd->prepare('
		INSERT INTO qrcodes (zone) 
		VALUES (:zone)');
	$req->execute(array(
		'zone' => $zone
	));
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}
*/

//insertion nouveau flash dans la table FLASHS

try {
	$req = $bdd->prepare('
		INSERT INTO flashs (date_flash, joueur, qrcode) 
		VALUES (:date_flash, :joueur, :qrcode)');
	$req->execute(array(
		'date_flash' => $date_flash,
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
		FROM flashs,inscriptions
		WHERE flashs.joueur=inscriptions.joueur 
		AND inscriptions.equipe=:equipe");
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

//récupération du nombre de points total d'un joueur

try {
	$req = $bdd->prepare("
		SELECT COUNT(qrcode) as nbPoints
		FROM flashs
		WHERE flashs.joueur=:joueur");
	$req->execute(array(
		'joueur' => $joueur
	));
	if($req->rowCount()>=1){
		$ligne=$req->fetch();
		echo $ligne['nbPoints'];
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
		FROM flashs,inscriptions,qrcodes
		WHERE flashs.joueur=inscriptions.joueur 
		AND inscriptions.equipe=:equipe
		AND qrcodes.zone=:zone
		AND flashs.qrcode=qrcodes.id_qrcode");
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

//classement des équipes

try {
	$req = $bdd->prepare("
		SELECT equipe, COUNT(qrcode) as nbPoints
		FROM flashs,inscriptions
		WHERE flashs.joueur=inscriptions.joueur
		GROUP BY inscriptions.equipe
		ORDER BY nbPoints DESC");
	$req->execute();
	$result = $req->fetchAll();
	print_r($result);
}
catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

//classement des joueurs

try {
	$req = $bdd->prepare("
		SELECT joueur, COUNT(qrcode) as nbPoints
		FROM flashs
		GROUP BY joueur
		ORDER BY nbPoints DESC");
	$req->execute();
	$result = $req->fetchAll();
	print_r($result);
}
catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

?>