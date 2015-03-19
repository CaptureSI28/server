<?php

//insertion nouvelle partie dans la table PARTIE

if ($stmt = $connect->prepare("INSERT INTO partie VALUES ('".$id_partie."','".$date_debut."','".$date_fin."')")) 
{
    $stmt->execute();
    $stmt->close();
}

//insertion nouveau joueur dans la table EQUIPE

if ($stmt = $connect->prepare("INSERT INTO equipe VALUES (NULL,'".$partie."','".$equipe."')"))            //NULL --> id_partie en AUTO_INCREMENT
{
    $stmt->execute();
    $stmt->close();
}

//insertion nouveau joueur dans la table JOUEURS

if ($stmt = $connect->prepare("INSERT INTO joueur VALUES ('".$login_joueur."','".$equipe."')")) 
{
    $stmt->execute();
    $stmt->close();
}

//insertion données dans la table FLASHER

if ($stmt = $connect->prepare("INSERT INTO flasher VALUES (NOW(),'".$login_joueur."','".$qrcode."')")) 
{
    $stmt->execute();
    $stmt->close();
}

?>