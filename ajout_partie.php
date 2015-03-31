<?php

//connection BDD
require_once('db_connect.php');

//affichage parties créées
$req = $bdd->query('
	SELECT *
	FROM parties;');
while ($row = $req->fetch()) {
	echo "<br>ID partie : ".$row[0];
	echo "<br>date debut : ".$row[1];
	echo "<br>date fin : ".$row[2];
	echo "<br>-----------------------";
} 

echo "<br><br><br><br>";

//cases à remplir
?>
<form method="post" action="ajout_partie.php">
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

if ((!empty($_POST["date_debut"])))
{
	if ($_POST["date_debut"]<$_POST["date_fin"])
	{
		if (empty($_POST["password"]))
		{
			try {
				$req = $bdd->prepare('
					INSERT INTO parties (date_debut, date_fin) 
					VALUES (:date_debut, :date_fin)');
				$req->execute(array(
					'date_debut' => $_POST["date_debut"],
					'date_fin' => $_POST["date_fin"]
				));
			} catch (Exception $e) {
				die('Error: ' . $e->getMessage());
			}
		}
		else
		{
			try {
				$req = $bdd->prepare('
					INSERT INTO parties (date_debut, date_fin, password) 
					VALUES (:date_debut, :date_fin, :password)');
				$req->execute(array(
					'date_debut' => $_POST["date_debut"],
					'date_fin' => $_POST["date_fin"],
					'password' => sha1($_POST["password"])
				));
			} catch (Exception $e) {
				die('Error: ' . $e->getMessage());
			}
		}
	}
	else echo "dates incompatibles";
}

?>
