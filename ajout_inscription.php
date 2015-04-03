<?php

//connection BDD

require_once('db_connect.php');

//affichage inscriptions créées

$req = $bdd->query('
	SELECT *
	FROM inscriptions;');
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
	<input type="text" placeholder="password (s'il y en a un)" name="password">
	<br><br>
	<input type="submit" value="inserer">
	<br>
	<input type="reset" value="effacer">
</form>
<?php

//insertion nouvelle inscription dans la table INSCRIPTIONS

//vérif : la partie à laquelle on s'inscrit doit exister

if (!empty($_POST["partie"]))
{
	$verif = $bdd->prepare('
		SELECT COUNT(id_partie)
		FROM parties 
		WHERE id_partie = :partie');
	$verif->execute(array(
				'partie' => $_POST["partie"]
			));
	$nb = $verif->fetchColumn();	//nombre de parties correspondant à l'ID entré (0 ou 1)

//verif2 : le joueur qu'on veut inscrire doit exister

	$verif2 = $bdd->prepare('
		SELECT COUNT(id_joueur)
		FROM joueurs 
		WHERE id_joueur = :joueur');
	$verif2->execute(array(
				'joueur' => $_POST["joueur"]
			));
	$nb2 = $verif2->fetchColumn();	//nombre de joueurs correspondant à l'ID entré (0 ou 1)

//verif3 : le joueur ne doit pas déjà être inscrit à la partie
	$verif3 = $bdd->prepare('
		SELECT COUNT(id_inscription)
		FROM inscriptions 
		WHERE partie = :partie
		AND joueur = :joueur');
	$verif3->execute(array(
				'partie' => $_POST["partie"],
				'joueur' => $_POST["joueur"]
			));
	$nb3 = $verif3->fetchColumn();	//nombre de fois où le joueur s'est inscrit à la partie (0 ou 1)

	
	if (($nb != 0)&&($_POST["equipe"]>=1)&&($_POST["equipe"]<=4)&&($nb2 != 0)&&($nb3 != 1))
	{
		//recherche du mot de passe de la partie
		try {
				$req = $bdd->prepare('
					SELECT password 
					FROM parties 
					WHERE id_partie = :partie');
				$req->execute(array(
					'partie' => $_POST["partie"]
				));
			} catch (Exception $e) {
				die('Error: ' . $e->getMessage());
			}

		while ($row = $req->fetch()) {
			//vérification de la correspondance du mot de passe
			if (($row[0]!=NULL)&&($row[0]!=sha1($_POST["password"])))
			{
				echo"mauvais mot de passe";
			}
			else 
			{
				try {
					$req = $bdd->prepare('
						INSERT INTO inscriptions (date_inscription, partie, equipe, joueur) 
						VALUES (:date_insc, :partie, :equipe, :joueur)');
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
		}
	}
	else echo "ce joueur est deja inscrit à la partie, ou bien la partie, l'equipe ou le joueur n'existe pas";

}
?>
