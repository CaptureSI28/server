<?php

//connection BDD

require_once('fonctions.php');

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

if (!empty($_POST["partie"]))
	if (inscrire ($_POST["date_insc"], $_POST["partie"], $_POST["equipe"], $_POST["joueur"], $_POST["password"]) == false)
		echo "problem";

?>
