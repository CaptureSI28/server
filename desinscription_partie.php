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

<form method="post" action="desinscription_partie.php">
	<input type="text" placeholder="partie" name="partie">
	<br><br>
	<input type="text" placeholder="joueur" name="joueur">
	<br><br>
	<input type="submit" value="se desinscrire">
	<br>
	<input type="reset" value="effacer">
</form>

<?php
if (!empty($_POST["partie"]))
{
	try {
			$req = $bdd->prepare('
				DELETE FROM inscriptions
				WHERE partie = :partie
				AND joueur = :joueur');
			$req->execute(array(
				'partie' => $_POST["partie"],
				'joueur' => $_POST["joueur"]
			));
		} catch (Exception $e) {
			die('Error: ' . $e->getMessage());
		}
}
?>