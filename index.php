<?php
	$dbconn = pg_connect('host=' . DB_HOST . ' dbname=' . DB_NAME . ' user=' . DB_USER . ' password=' . DB_PASSWORD);
//	echo json_encode($_POST);
//	$login = $_POST['login'];
	$login = 'joueur1';
	$data = $_POST['data'];
	try {
		$res = pg_prepare('', "
			INSERT INTO flashes (date_flash, joueur, qrcode)
			VALUES (NOW(), (
				SELECT id_joueur
				FROM joueurs
				WHERE identifiant = $1
				LIMIT 1
			), $2);
		");
		$res = pg_execute('', array(
			$login,
			$data
		));
		echo 'Success';
	} catch (Exception $e) {
		die('Error:' . $e->getMessage());
	}
?>
