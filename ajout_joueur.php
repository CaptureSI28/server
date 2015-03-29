<?php

//connection BDD

require_once('db_connect.php');

//affichage joueurs créés

$connect = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname) or die ('Error connecting to mysql');
$req="SELECT * FROM joueurs";
$result = mysqli_query($connect,$req);
while ($row = mysqli_fetch_array($result, MYSQL_NUM))
	{
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
<?php

//insertion nouvelle partie dans la table PARTIE

if (!empty($_POST["login"]))
{

try {
	$req = $bdd->prepare('INSERT INTO joueurs (login) VALUES (:login)');
	$req->execute(array(
		'login' => $_POST["login"]
	));
} catch (Exception $e) {
	die('Error: ' . $e->getMessage());
}

}

?>