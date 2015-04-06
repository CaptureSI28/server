<?php

//connection BDD
require_once('fonctions.php');

//affichage joueurs créés
$req = $bdd->query('
	SELECT *
	FROM joueurs
	ORDER BY id_joueur;');
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

if(!empty($_POST["login"]))
	if (newPlayer($_POST["login"]) == false)
		echo "problem";

?>