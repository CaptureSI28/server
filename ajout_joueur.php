<?php

//connection BDD
require_once('db_connect.php');

//affichage joueurs créés
$req = $bdd->query('
	SELECT *
	FROM joueurs;');
while ($row = $req->fetch()) {
	echo "<br>ID joueur : ".$row[0];
	echo "<br>login : ".$row[1];
	echo "<br>-----------------------";
} 

echo "<br><br><br><br>";

//cases à remplir
?>
<form method="post" action="ajout_joueur.php">
	<input type="text" placeholder="login" name="login">
	<br><br>
	<input type="submit" value="inserer">
	<br>
	<input type="reset" value="effacer">
</form>
<?php

//insertion nouveau joueur dans la table JOUEURS

if (!empty($_POST["login"]))
{
	$verif = $bdd->prepare('
		SELECT COUNT(login)
		FROM joueurs 
		WHERE login = :login');
	$verif->execute(array(
				'login' => $_POST["login"]
			));
	$nb = $verif->fetchColumn();

	if ($nb == 0)
	{
		try {
			$req = $bdd->prepare('
				INSERT INTO joueurs (login) 
				VALUES (:login)');
			$req->execute(array(
				'login' => $_POST["login"]
			));
		} catch (Exception $e) {
			die('Error: ' . $e->getMessage());
		}
	}
	else echo "le joueur existe deja";
}
?>
