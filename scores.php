<?php
	$dbconn = pg_connect('host=' . DB_HOST . ' dbname=' . DB_NAME . ' user=' . DB_USER . ' password=' . DB_PASSWORD);
	$res = pg_prepare('', "
		SELECT id_qrcode, (
			SELECT ce.couleur
			FROM flashes f, joueurs j, equipes e, couleurs_equipes ce
			WHERE q.id_qrcode = f.qrcode AND
				f.joueur = j.id_joueur AND
				j.equipe = e.id_equipe AND
				e.couleur_equipe = ce.id_couleur_equipe
			ORDER BY date_flash DESC
			LIMIT 1
		) AS couleur
		FROM qrcodes q
		GROUP BY id_qrcode;
	");
	$res = pg_execute('', array());
	echo '<ul>';
	while ($row = pg_fetch_array($res)) {
		echo '<li>QRCode ' . $row['id_qrcode'] . ' capture par l\'equipe "' . $row['couleur'] . '"</li>';
	}
	echo '</ul>';
?>
