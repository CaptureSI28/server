<?php

//connection BDD

$dbhost = 'localhost';
$dbuser = 'root'; 
$dbpass = '';
$dbname = 'si28';
$connect = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname) or die ('Error connecting to mysql');

//ajout d'un flash dans la BDD

//$login_joueur="joueur3";
//$qrcode="42";
$login_joueur=$_POST['login'];
$qrcode=$_POST['data'];

$query = "INSERT INTO flasher VALUES (NOW(),'".$login_joueur."','".$qrcode."')";
echo "<br>$query<br>";
$result=mysqli_query($connect, $query);
if (!$result) 
	{echo "<br>pas bon : ".mysqli_error($connect);}

?>