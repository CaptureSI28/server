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


/* 
 * Input:
 * - login: login du joueur à ajouter
 *
 * Output:
 * -booléen: true si tout se passe bien, false sinon 
 */
function creerJoueur ($login) {
	global $bdd;
	$verif = $bdd->prepare('
			SELECT COUNT(login)
			FROM joueurs 
			WHERE login = :login');
		$verif->execute(array(
					'login' => $login
				));
		$nb = $verif->fetchColumn();

	if ($nb == 0)
	{
		try {
			$req = $bdd->prepare('
				INSERT INTO joueurs (login) 
				VALUES (:login)');
			$req->execute(array(
				'login' => $login
			));
		} catch (Exception $e) {
			die('Error: ' . $e->getMessage());
		}
		return true;
	}
	else
		return false;
}

/* 
 * Input:
 * - date_insc: date d'inscription du joueur (DATETIME)
 * - partie: ID de la partie à laquelle s'inscrire
 * - equipe: équipe dans laquelle s'inscrire
 * - joueur: ID du joueur à inscrire
 * - password: chaine de caractères ('NULL' si non renseigné)
 *
 * Output:
 * -booléen: true si tout se passe bien, false sinon
 */

function inscrire ($date_insc, $partie, $equipe, $joueur, $password) {
	global $bdd;	

	//vérif : la partie à laquelle on s'inscrit doit exister
	$verif = $bdd->prepare('
		SELECT COUNT(id_partie)
		FROM parties 
		WHERE id_partie = :partie');
	$verif->execute(array(
				'partie' => $partie
			));
	$nb = $verif->fetchColumn();	//nombre de parties correspondant à l'ID entré (0 ou 1)

	//verif2 : le joueur qu'on veut inscrire doit exister
	$verif2 = $bdd->prepare('
		SELECT COUNT(id_joueur)
		FROM joueurs 
		WHERE id_joueur = :joueur');
	$verif2->execute(array(
				'joueur' => $joueur
			));
	$nb2 = $verif2->fetchColumn();	//nombre de joueurs correspondant à l'ID entré (0 ou 1)

	//verif3 : le joueur ne doit pas déjà être inscrit à la partie
	$verif3 = $bdd->prepare('
		SELECT COUNT(id_inscription)
		FROM inscriptions 
		WHERE partie = :partie
		AND joueur = :joueur');
	$verif3->execute(array(
				'partie' => $partie,
				'joueur' => $joueur
			));
	$nb3 = $verif3->fetchColumn();	//nombre de fois où le joueur s'est inscrit à la partie (0 ou 1)
	
	if (($nb != 0)&&($equipe >= 1)&&($equipe <= 4)&&($nb2 != 0)&&($nb3 != 1))
	{
		//recherche du mot de passe de la partie
		try {
				$req = $bdd->prepare('
					SELECT password 
					FROM parties 
					WHERE id_partie = :partie');
				$req->execute(array(
					'partie' => $partie
				));
			} catch (Exception $e) {
				die('Error: ' . $e->getMessage());
			}
		while ($row = $req->fetch()) 
		{
			//vérification de la correspondance du mot de passe
				if (($row[0] != NULL)&&($row[0]!=sha1($password)))
				{
					return false;
				}
				else 
				{
					try {
							$req = $bdd->prepare('
								INSERT INTO inscriptions (date_inscription, partie, equipe, joueur) 
								VALUES (:date_insc, :partie, :equipe, :joueur)');
							$req->execute(array(
								'date_insc' => $date_insc,
								'partie' => $partie,
								'equipe' => $equipe,
								'joueur' => $joueur
							));
						} catch (Exception $e) {
							die('Error: ' . $e->getMessage());
						}
					return true;
				}
		}
	}
}




/* A FINIR
 * Input:
 * - joueurID: identifiant du joueur
 *
 * Output:
 * - partieID: identifiant de la partie à laquelle est actuellement inscrit le joueur
 */
function getPartieActiveJoueur ($joueurID) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT partie
		FROM inscriptions
		WHERE joueur = :joueur
		ORDER BY date_inscription DESC
		LIMIT 1');
	$req->execute(array(
		'joueur' => $joueurID
	));
	if ($row = $req->fetch()) {
		return $row['partie'];
	} else {
		return 0;
	}
}

/* TEMPLATE
 * Input:
 * - param1: description du param1
 * - param2: description du param2
 *
 * Output:
 * - sortie: description de la sortie (specifier lorsque c'est un tableau)
 */

/* TEMPLATE fonction
function historique ($argument) {
	global $bdd;


}
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
function loggerCAS ($argument) {
	global $bdd;


}
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
function getEquipeJoueur ($argument) {
	global $bdd;


}
*/












?>