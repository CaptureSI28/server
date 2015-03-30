<?php
	require_once('db_connect.php');
	$result = array('success' => 'NO');
	$login = $_POST['login'];
	$data = $_POST['data'];
	
	$req = $bdd->prepare('
		SELECT *, (
			SELECT COUNT(*)
			FROM inscriptions
			WHERE joueur = j.id_joueur
		) AS num_inscriptions
		FROM joueurs j
		WHERE login = ?;
	');
	$req->execute(array($login));
	if ($row = $req->fetch()) {
		$nb_inscriptions = $row['num_inscriptions'];
	} else {
		$nb_inscriptions = 0;
		$req = $bdd->prepare('
			INSERT INTO joueurs (login)
			VALUES (?);
		');
		$req->execute(array($login));
	}
	if ($nb_inscriptions == 0) {
		try {
			$req = $bdd->prepare('
				INSERT INTO inscriptions (date_inscription, partie, equipe, joueur)
				VALUES (NOW(), (
					SELECT id_partie
					FROM parties
					ORDER BY date_debut DESC
					LIMIT 1
				), (
					SELECT id_equipe
					FROM equipes
					ORDER BY RAND()
					LIMIT 1
				), (
					SELECT id_joueur
					FROM joueurs
					WHERE login = ?
					LIMIT 1
				));
			');
			$req->execute(array($login));
		} catch (Exception $e) {
			$result['message'] = 'Error: Vous n\'êtes inscrit dans aucune partie';
		}
	}
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
		$result['success'] = 'YES';
	} catch (Exception $e) {
		$result['message'] = 'Error: ' . $e->getMessage();
		die(json_encode($result));
	}
?>
