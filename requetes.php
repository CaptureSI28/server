<?php

//insertion nouvelle partie dans la table PARTIE




//insertion nouveau joueur dans la table JOUEURS





//insertion données dans la table FLASHER

$query = "INSERT INTO flasher VALUES ('".$date."','".$login_joueur."','".$qrcode."')";
echo "<br>$query<br>";
$result=mysqli_query($connect, $query);
if (!$result) 
	{echo "<br>pas bon : ".mysqli_error($connect);}

?>