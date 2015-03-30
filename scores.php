<?php
	require_once('db_connect.php');
?>
	<style type="text/css">
		body {
			margin: 0 auto;
			width: 500px;
		}
		table {
			border-collapse: collapse;
		}
		table th, table td {
			padding: 20px;
			border: 1px solid black;
			text-align: center;
		}
		table th {
			background-color: lightgrey;
		}
		table tr {
			padding: 10px;
		}
<?php
		$req = $bdd->query('
			SELECT *
			FROM equipes;
		');
	while ($row = $req->fetch()) {
?>
		.color<?php echo $row['id_equipe']; ?> {
			background-color: <?php echo $row['hexcolor']; ?>;
		}
<?php
	}
?>
	</style>
<?php
	try {
		$req = $bdd->prepare('
			SELECT a.*, i.date_flash, i.id_inscription, i.equipe, (
				SELECT login
				FROM joueurs
				WHERE i.joueur = id_joueur
			) AS joueur
			FROM all_qrcodes a
			LEFT JOIN infos_flashs i
			ON a.id_flash = i.id_flash;
		');
		$req->execute(array());
	} catch (Exception $e) {
		echo $e->getMessage();
	}
	$qrcodes = array();
	while ($row = $req->fetch()) {
		$qrcodes[] = $row;
	}
?>
	<h2>État des captures des qrcodes:</h2>
	<table>
		<tr>
			<th>Partie</th>
			<th>Zone</th>
			<th>QRCode</th>
			<th>Capturé par</th>
		</tr>
<?php
	$nb_parties = array();
	foreach ($qrcodes as $qrcode) {
		$partie = $qrcode['partie'];
		if (!isset($nb_parties[$partie])) {
			$nb_parties[$partie] = array(
				'count' => 0,
				'zones' => array()
			);
		}
		$nb_zones = &$nb_parties[$partie]['zones'];
		$zone = $qrcode['zone'];
		if (!isset($nb_zones[$zone])) {
			$nb_zones[$zone] = 0;
		}
		$nb_parties[$partie]['count']++;
		$nb_zones[$zone]++;
	}
	$last_partie = NULL;
	$last_zone = NULL;
	foreach ($qrcodes as $row) {
		$nb_zones = $nb_parties[$partie]['zones'];
		$partie = $row['partie'];
		$zone = $row['zone'];
?>
		<tr>
<?php
		if ($last_partie != $partie) {
			$last_partie = $partie;
?>
			<td class="color<?php echo $row['equipe_partie']; ?>" rowspan="<?php echo $nb_parties[$partie]['count']; ?>">
				<?php echo $row['partie']; ?>
				<br />
				<?php echo date('d/m/y H:i:s', strtotime($row['date_debut'])) . ' - ' . date('d/m/y H:i:s', strtotime($row['date_fin'])); ?>
			</td>
<?php
		}
		if ($last_zone != $zone) {
			$last_zone = $zone;
?>
			<td class="color<?php echo $row['equipe_zone']; ?>" rowspan="<?php echo $nb_zones[$zone]; ?>"><?php echo $row['zone']; ?></td>
<?php
		}
?>
			<td class="color<?php echo $row['equipe']; ?>">
				<img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&amp;data=<?php echo $row['qrcode']; ?>">
				<br />
				<?php echo $row['qrcode']; ?>
			</td>
<?php
		if ($row['joueur']) {
?>
			<td>
				<img height="80" src="https://demeter.utc.fr/pls/portal30/portal30.get_photo_utilisateur?username=<?php echo $row['joueur']; ?>">
				<br />
				<?php echo $row['joueur']; ?>
			</td>
<?php
		} else {
?>
			<td colspan="2">Non capturé</td>
<?php
		}
?>
		</tr>
<?php
	}
?>
	</table>
<?php
	$req->closeCursor();
?>
