<?php

require_once('fonctions.php');

if ((empty($_POST["choix"]))&&(empty($_POST["date_inscription"])))	//1ère page : choix du champ à modifier
{
?>
<form method="post" action="interface_admin.php">
	<SELECT name="choix" size="1">
	<OPTION>
	<OPTION>partie
	<OPTION>joueur
	<OPTION>inscription
	<OPTION>flash
	<OPTION>qrcode
	</SELECT>
	<br><br>
	<input type="text" placeholder="ID du champ a modifier" name="id">	
	<br><br>
	<input type="submit" value="submit">
	<br>
</form>
<?php
}
if ((!empty($_POST["choix"]))&&(!empty($_POST["id"])))	//2ème page : affichage du champ puis formulaire pour changements
	{
		switch ($_POST["choix"]) 
		{
			case "partie":
				$req = $bdd->prepare('
					SELECT *
					FROM parties
					WHERE id_partie = :id');
				$req->execute(array(
					'id' => $_POST["id"]
				));
				while ($row = $req->fetch()) {
					echo "<br>ID partie : ".$row[0];
					echo "<br>nom : ".$row[1];
					echo "<br>date debut : ".$row[3];
					echo "<br>date fin : ".$row[4];
					echo "<br>-----------------------";
				} 
				$id=$_POST["id"];
				$choix=$_POST["choix"];
				?>
				<form method="post" action="interface_admin.php">
					<input type="text" placeholder="nom" name="nom">	
					<br><br>
					<input type="text" placeholder="password" name="password">	
					<br><br>
					<input type="text" placeholder="date debut" name="date_debut">	
					<br><br>
					<input type="text" placeholder="date fin" name="date_fin">	
					<br><br>
					<input type="HIDDEN" name="id_2" value="<?php echo $id ?>"> 
					<input type="HIDDEN" name="choix" value="<?php echo $choix ?>"> 
					<input type="submit" value="submit">
					<br>
				</form>
				<?php
				break;
				
			case "joueur":
				$req = $bdd->prepare('
					SELECT *
					FROM joueurs
					WHERE id_joueur = :id');
				$req->execute(array(
					'id' => $_POST["id"]
				));
				while ($row = $req->fetch()) {
					echo "<br>ID joueur : ".$row[0];
					echo "<br>login : ".$row[1];
					echo "<br>-----------------------";
				} 
				$id=$_POST["id"];
				$choix=$_POST["choix"];
				?>
				<form method="post" action="interface_admin.php">	
					<input type="text" placeholder="login" name="login">	
					<br><br>
					<input type="HIDDEN" name="id_2" value="<?php echo $id ?>"> 
					<input type="HIDDEN" name="choix" value="<?php echo $choix ?>"> 
					<input type="submit" value="submit">
					<br>
				</form>
				<?php
				break;

			case "inscription":
				$req = $bdd->prepare('
					SELECT *
					FROM inscriptions
					WHERE id_inscription = :id');
				$req->execute(array(
					'id' => $_POST["id"]
				));
				while ($row = $req->fetch()) {
						echo "<br>ID inscription : ".$row[0];
						echo "<br>date d'inscription : ".$row[1];
						echo "<br>partie : ".$row[2];
						echo "<br>equipe : ".$row[3];
						echo "<br>joueur : ".$row[4];
						echo "<br>-----------------------<br><br>";
					}
				$id=$_POST["id"];
				$choix=$_POST["choix"];
				?>
				<form method="post" action="interface_admin.php">
					<input type="text" placeholder="date d'inscription" name="date_inscription">	
					<br><br>
					<input type="text" placeholder="partie" name="partie">	
					<br><br>
					<input type="text" placeholder="equipe" name="equipe">	
					<br><br>
					<input type="text" placeholder="joueur" name="joueur">	
					<br><br>
					<input type="HIDDEN" name="id_2" value="<?php echo $id ?>"> 
					<input type="HIDDEN" name="choix" value="<?php echo $choix ?>"> 
					<input type="submit" value="submit">
					<br>
				</form>
				<?php
				break;

			case "flash":
				$req = $bdd->prepare('
					SELECT *
					FROM flashs
					WHERE id_flash = :id');
				$req->execute(array(
					'id' => $_POST["id"]
				));
				while ($row = $req->fetch()) {
						echo "<br>ID flash : ".$row[0];
						echo "<br>date flash : ".$row[1];
						echo "<br>joueur : ".$row[2];
						echo "<br>qrcode : ".$row[3];
						echo "<br>-----------------------";
				}
				$id=$_POST["id"];
				$choix=$_POST["choix"];
				?>
				<form method="post" action="interface_admin.php">
					<input type="text" placeholder="date flash" name="date_flash">	
					<br><br>
					<input type="text" placeholder="joueur" name="joueur">	
					<br><br>
					<input type="text" placeholder="qrcode" name="qrcode">	
					<br><br>
					<input type="HIDDEN" name="id_2" value="<?php echo $id ?>"> 
					<input type="HIDDEN" name="choix" value="<?php echo $choix ?>"> 
					<input type="submit" value="submit">
					<br>
				</form>
				<?php
				break;

			case "qrcode":
				$req = $bdd->prepare('
					SELECT *
					FROM qrcodes
					WHERE id_qrcode = :id');
				$req->execute(array(
					'id' => $_POST["id"]
				));
				while ($row = $req->fetch()) {
					echo "<br>ID qrcode : ".$row[0];
					echo "<br>zone : ".$row[1];
					echo "<br>-----------------------";
				} 
				$id=$_POST["id"];
				$choix=$_POST["choix"];
				?>
				<form method="post" action="interface_admin.php">
					<input type="text" placeholder="zone" name="zone">	
					<br><br>
					<input type="HIDDEN" name="id_2" value="<?php echo $id ?>"> 
					<input type="HIDDEN" name="choix" value="<?php echo $choix ?>"> 
					<input type="submit" value="submit">
					<br>
				</form>
				<?php
				break;
		}
	}
if (!empty($_POST["id_2"]))
	{
		switch ($_POST["choix"])
		{
			case "partie":
				$req = $bdd->prepare('
					UPDATE parties
					SET nom = :nom,
						password = :password,
						date_debut = :date_debut,
						date_fin = :date_fin
					WHERE id_partie = :id');
				$req->execute(array(
					'id' => $_POST["id_2"],
					'nom' => $_POST["nom"],
					'password' => $_POST["password"],
					'date_debut' => $_POST["date_debut"],
					'date_fin' => $_POST["date_fin"]
				));
				break;

			case "joueur":
				$req = $bdd->prepare('
					UPDATE joueurs
					SET login = :login
					WHERE id_joueur = :id');
				$req->execute(array(
					'id' => $_POST["id_2"],
					'login' => $_POST["login"]
				));
				break;

			case "inscription":
				$req = $bdd->prepare('
					UPDATE inscriptions
					SET date_inscription = :date_inscription,
						partie = :partie,
						equipe = :equipe,
						joueur = :joueur
					WHERE id_inscription = :id');
				$req->execute(array(
					'id' => $_POST["id_2"],
					'date_inscription' => $_POST["date_inscription"],
					'partie' => $_POST["partie"],
					'equipe' => $_POST["equipe"],
					'joueur' => $_POST["joueur"]
				));
				break;

			case "flash":
				$req = $bdd->prepare('
					UPDATE flashs
					SET date_flash = :date_flash,
						joueur = :joueur,
						qrcode = :qrcode
					WHERE id_flash = :id');
				$req->execute(array(
					'id' => $_POST["id_2"],
					'date_flash' => $_POST["date_flash"],
					'joueur' => $_POST["joueur"],
					'qrcode' => $_POST["qrcode"]
				));
				break;

			case "qrcode":
				$req = $bdd->prepare('
					UPDATE qrcodes
					SET zone = :zone
					WHERE id_qrcode = :id');
				$req->execute(array(
					'id' => $_POST["id_2"],
					'zone' => $_POST["zone"]
				));
				break;
		}
	}
?>