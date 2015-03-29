<?php

//connection BDD

require_once('db_connect.php');

//affichage parties créées

$connect = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname) or die ('Error connecting to mysql');
$req="SELECT * FROM parties";
$result = mysqli_query($connect,$req);
while ($row = mysqli_fetch_array($result, MYSQL_NUM))
	{
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
<input type="submit" value="inserer">
<br>
<input type="reset" value="effacer">
<?php

//insertion nouvelle partie dans la table PARTIE

if (!empty($_POST["date_debut"]))
{

try {
	$req = $bdd->prepare('INSERT INTO parties (date_debut, date_fin) VALUES (:date_debut, :date_fin)');
	$req->execute(array(
		'date_debut' => $_POST["date_debut"],
		'date_fin' => $_POST["date_fin"]
	));
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

}

?>