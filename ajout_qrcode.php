<?php

//connection BDD

require_once('db_connect.php');

//affichage inscriptions créées

$req = $bdd->query('
	SELECT *
	FROM qrcodes;
');

$zone_prec="-1";

while ($row = $req->fetch()) {
	if ($zone_prec!=$row[1]){
		echo "<br>-----------------------";
		echo "<br>zone : ".$row[1];
		echo "<br><br>ID qrcode : ".$row[0];
	}
	else{
		echo "<br>ID qrcode : ".$row[0];
	}
	$zone_prec=$row[1];
}

echo "<br><br><br><br>";

//cases à remplir
?>
<form method="post" action="ajout_qrcode.php">
	<input type="text" placeholder="zone" name="zone">	
	<br><br>
	<input type="submit" value="inserer">
	<br>
	<input type="reset" value="effacer">
</form>
<?php

//insertion nouveau qrcode dans la table QRCODES

if (!empty($_POST["zone"]))
{
	$verif = $bdd->prepare('
		SELECT COUNT(*)
		FROM zones 
		WHERE id_zone = :zone');
	$verif->execute(array(
				'zone' => $_POST["zone"]
			));
	$nb = $verif->fetchColumn();

	if ($nb != 0)
	{
		try {
			$req = $bdd->prepare('INSERT INTO qrcodes (zone) VALUES (:zone)');
			$req->execute(array(
				'zone' => $_POST["zone"]
			));
		} catch (Exception $e) {
			die('Error: ' . $e->getMessage());
		}
	}
	else echo "zone inexistante";
}