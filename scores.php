<?php
	require_once('db_connect.php');
?>
	<style type="text/css">
<?php
		$req = $bdd->query('
			SELECT *
			FROM equipes;
		');
	while ($row = $req->fetch()) {
?>
		.color<?php echo $row['id_equipe']; ?> {
			background-color: <?php echo $row['hexcolor']; ?>;
			padding: 10px;
		}
<?php
	}
?>
	</style>
<?php
	try {
		$req = $bdd->prepare('
			SELECT id_qrcode, equipe, (
				SELECT hexcolor
				FROM equipes e
				WHERE q.equipe = e.id_equipe
			) AS hexcolor
			FROM (
				SELECT id_qrcode, (
					SELECT (
						SELECT equipe
						FROM inscriptions i
						WHERE j.id_joueur = i.joueur
							AND i.date_inscription < date_flash
						ORDER BY date_inscription
						LIMIT 1
					) AS equipe
					FROM flashs f, joueurs j
					WHERE q.id_qrcode = f.qrcode
						AND f.joueur = j.id_joueur
					ORDER BY date_flash DESC
					LIMIT 1
				) AS equipe
				FROM qrcodes q
			) q;
		');
		$req->execute(array());
	} catch (Exception $e) {
		echo $e->getMessage();
	}
	echo '<p>État des captures des qrcodes:</p>';
	echo '<ul>';
	while ($row = $req->fetch()) {
		if ($row['equipe']) {
			echo '<li class="color' . $row['equipe'] . '">Le QRCode ' . $row['id_qrcode'] . ' a été capturé par l\'équipe "' . $row['equipe'] . '"</li>';
		} else {
			echo '<li>QRCode ' . $row['id_qrcode'] . ' n\'a pas encore été capturé</li>';
		}
	}
	echo '</ul>';
	$req->closeCursor();
?>
