<?php

//connection BDD

require_once('db_connect.php');

//affichage inscriptions créées

$req = $bdd->query('
	SELECT *
	FROM inscriptions;
');
while ($row = $req->fetch()) {
		echo "<br>ID inscription : ".$row[0];
		echo "<br>date d'inscription : ".$row[1];
		echo "<br>partie : ".$row[2];
		echo "<br>equipe : ".$row[3];
		echo "<br>joueur : ".$row[4];
		echo "<br>-----------------------";
}

echo "<br><br><br><br>";

//cases à remplir
?>
<form method="post" action="ajout_inscription.php">
	<input type="text" placeholder="date_inscription (Y-m-d H:m:s)" name="date_insc">	
	<br><br>
	<input type="text" placeholder="partie" name="partie">
	<br><br>
	<input type="text" placeholder="equipe" name="equipe">
	<br><br>
	<input type="text" placeholder="joueur" name="joueur">
	<br><br>
	<input type="submit" value="inserer">
	<br>
	<input type="reset" value="effacer">
</form>
<?php

//insertion nouvelle inscription dans la table INSCRIPTIONS

if (!empty($_POST["partie"]))
{
	$verif = $bdd->prepare('
		SELECT COUNT(id_partie)
		FROM parties 
		WHERE id_partie = :partie');
	$verif->execute(array(
				'partie' => $_POST["partie"]
			));
	$nb = $verif->fetchColumn();

	$verif2 = $bdd->prepare('
		SELECT COUNT(id_joueur)
		FROM joueurs 
		WHERE id_joueur = :joueur');
	$verif2->execute(array(
				'joueur' => $_POST["joueur"]
			));
	$nb2 = $verif2->fetchColumn();
	
	if (($nb != 0)&&($_POST["equipe"]>=1)&&($_POST["equipe"]<=4)&&($nb2 != 0))
	{
		try {
			$req = $bdd->prepare('INSERT INTO inscriptions (date_inscription, partie, equipe, joueur) VALUES (:date_insc, :partie, :equipe, :joueur)');
			$req->execute(array(
				'date_insc' => $_POST["date_insc"],
				'partie' => $_POST["partie"],
				'equipe' => $_POST["equipe"],
				'joueur' => $_POST["joueur"]
			));
		} catch (Exception $e) {
			die('Error: ' . $e->getMessage());
		}
	}
	else echo "la partie, l'equipe ou le joueur n'existe pas";

}
?>
