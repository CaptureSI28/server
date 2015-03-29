<?php
	require_once('db_connect.php');
//	echo json_encode($_POST);
	$login = $_POST['login'];
	$data = $_POST['data'];
	try {
		$req = $bdd->prepare('
			INSERT INTO flashs (date_flash, joueur, qrcode)
			VALUES (NOW(), (
				SELECT id_joueur
				FROM joueurs
				WHERE login = ?
				LIMIT 1
			), ?);
		');
		$req->execute(array(
			$login,
			$data
		));
		echo 'Success';
	} catch (Exception $e) {
		die('Error:' . $e->getMessage());
	}
?>
