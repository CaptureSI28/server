<?php

//connection BDD

require_once('fonctions.php');

//affichage inscriptions créées

$req = $bdd->query('
	SELECT *
	FROM flashs;');
while ($row = $req->fetch()) {
		echo "<br>ID flash : ".$row[0];
		echo "<br>date flash : ".$row[1];
		echo "<br>joueur : ".$row[2];
		echo "<br>qrcode : ".$row[3];
		echo "<br>nbpoint : ".$row[4];
		echo "<br>-----------------------";
}

echo getScoreEquipesPartie(1);
echo getNombreZonesEquipePartie(1,1);
echo getNombreZonesEquipePartie(1,2);
echo getNombreZonesEquipePartie(1,3);
echo getNombreZonesEquipePartie(1,4);

echo "<br><br><br><br>";

//cases à remplir

?>
<form method="post" action="ajout_flash.php">
	<input type="text" placeholder="date_flash (Y-m-d H:m:s)" name="date_flash">	
	<br><br>
	<input type="text" placeholder="joueur" name="joueur">
	<br><br>
	<input type="text" placeholder="qrcode" name="qrcode">
	<br><br>
	<input type="submit" value="inserer">
	<br>
	<input type="reset" value="effacer">
</form>
<?php

//insertion nouvelle partie dans la table FLASHS

if (!empty($_POST["qrcode"]))
{
	/*try {
		$req = $bdd->prepare('
			INSERT INTO flashs (date_flash, joueur, qrcode) 
			VALUES (:date_flash, :joueur, :qrcode)');
		$req->execute(array(
			'date_flash' => $_POST["date_flash"],
			'joueur' => $_POST["joueur"],
			'qrcode' => $_POST["qrcode"]
		));
	} catch (Exception $e) {
		die('Error: ' . $e->getMessage());
	}*/
	if (newFlash($_POST["date_flash"], $_POST["joueur"], $_POST["qrcode"]) == false)
		echo "problem";
}






?>