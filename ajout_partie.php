<?php

//connection BDD
require_once('fonctions.php');


//affichage parties créées
$req = $bdd->query('
	SELECT *
	FROM parties;');
while ($row = $req->fetch()) {
	echo "<br>ID partie : ".$row[0];
	echo "<br>nom : ".$row[1];
	echo "<br>date debut : ".$row[3];
	echo "<br>date fin : ".$row[4];
	echo "<br>-----------------------";
} 

echo "<br><br><br><br>";

//cases à remplir
?>
<form method="post" action="ajout_partie.php">
	<input type="text" placeholder="nom de la partie" name="nom">
	<br><br>
	<input type="text" placeholder="date_debut (Y-m-d H:m:s)" name="date_debut">
	<br><br>
	<input type="text" placeholder="date_fin (Y-m-d H:m:s)" name="date_fin">
	<br><br>
	<input type="text" placeholder="password (ou non)" name="password">
	<br><br>
	<input type="submit" value="inserer">
	<br>
	<input type="reset" value="effacer">
</form>
<?php

//insertion nouvelle partie dans la table PARTIE

if(!empty($_POST["date_debut"]))
	if (creerPartie($_POST["nom"], $_POST["date_debut"], $_POST["date_fin"], $_POST["password"]) == false)
		echo "probleme";
?>