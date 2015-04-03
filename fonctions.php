<?php

//connection BDD

require_once('db_connect.php');

/*
	Script contenant toutes les fonctions necessaires pour le site.
	Chaque fonction se devra de renvoyer la sortie demandee en fonction des parametres d'entree donnes.
	Chaque fonction devra specifier ses parametres d'entree et de sortie attendus en commentaires.
*/

/* TEMPLATE
 * Input:
 * - param1: description du param1
 * - param2: description du param2
 *
 * Output:
 * - sortie: description de la sortie (specifier lorsque c'est un tableau)
 */

/* TEMPLATE fonction
function nomFonction ($argument) {
	global $bdd;
}
*/


/*
 * Input:
 * - equipeID: identifiant d'une equipe
 *
 * Output:
 * - nbFlashs: nb flashs de l'equipe demandee
 */
function getNbFlashsEquipe ($equipeID) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT COUNT(qrcode) as nbQrcode
		FROM flashs,inscriptions
		WHERE flashs.joueur=inscriptions.joueur 
		AND inscriptions.equipe=:equipe');
	$req->execute(array(
		'equipe' => $equipeID
	));
	if ($row = $req->fetch()) {
		return $row['nbQrcode'];
	} else {
		return 0;
	}
}

/*
 * Input:
 * -nom: nom de la partie
 * -date_debut: Y-m-d H:m:s
 * -date_fin: Y-m-d H:m:s
 * -password: chaine de caractères ('NULL' si non renseigné)
 * Output:
 * -booléen: true si tout se passe bien, false sinon
 */
function creerPartie ($nom, $date_debut, $date_fin, $password) {
	global $bdd;
	if (($date_debut<$date_fin)&&($date_debut >= date('Y-m-d H:m:s', time())))
		{
			if ($password == 'NULL')
				{
					$req = $bdd->prepare('
						INSERT INTO parties (nom, date_debut, date_fin) 
						VALUES (:nom, :date_debut, :date_fin)');
					$req->execute(array(
						'nom' => $nom,
						'date_debut' => $date_debut,
						'date_fin' => $date_fin
					));
				}
			else
				{
					$req = $bdd->prepare('
						INSERT INTO parties (nom, date_debut, date_fin, password) 
						VALUES (:nom, :date_debut, :date_fin, :password)');
					$req->execute(array(
						'nom' => $nom,
						'date_debut' => $_POST["date_debut"],
						'date_fin' => $_POST["date_fin"],
						'password' => sha1($_POST["password"])
					));
				}
			return true;
		}
	else
		return false;
}










/* A FINIR
 * Input:
 * - joueurID: identifiant du joueur
 *
 * Output:
 * - partieID: identifiant de la partie à laquelle est actuellement inscrit le joueur
 */
function getPartieActiveJoueur ($joueurID) {
	$req = $bdd->prepare('
		');
	$req->execute(array(
		'equipe' => $equipeID
	));
	if ($row = $req->fetch()) {
		return $row['partie'];
	} else {
		return 0;
	}
}

?>