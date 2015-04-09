<?php
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
 * - login: login du joueur a ajouter
 *
 * Output:
 * -booleen: true si tout se passe bien, false sinon 
 */
function newPlayer ($login) {
	global $bdd;
	try {
		$req = $bdd->prepare('
			INSERT INTO joueurs (login)
			VALUES (:login)
		');
		$req->execute(array(
			'login' => $login
		));
	} catch (Exception $e) {
		return false;
	}
	return true;
}

/*
 * Input:
 * -date: date du flash DATETIME
 * -joueur: id du joueur qui flash
 * -qrcode: id du qrcode flashé
 *
 * Output:
 * -booleen: true si tout se passe bien, false sinon 
 */
function newFlash ($date, $joueur, $qrcode) {
	global $bdd;
	try {
	$req = $bdd->prepare('
			INSERT INTO flashs (date_flash, joueur, qrcode) 
			VALUES (:date, :joueur, :qrcode)');
		$req->execute(array(
			'date' => $date,
			'joueur' => $joueur,
			'qrcode' => $qrcode
		));
	} catch (Exception $e) {
		return false;
	}
	return true;
}

/*
 * Input:
 * - ticket: ticket CAS valide
 * - service: service CAS
 *
 * Output:
 * - Array contenant (champs facultatifs selon reussite):
 *  - success: login CAS
 *  - session_id: id de session PHP
 *  - failure: code d'erreur CAS
 */
function validateCasTicket ($ticket, $service) {
	$casURL = 'https://cas.utc.fr/cas/';
	$casResponse = file_get_contents($casURL . 'serviceValidate?ticket=' . urlencode($ticket) . '&service=' . urlencode($service));
	$casResponse = preg_replace('/(?<=\\<)(\/?)cas:/', '$1', $casResponse);
	$xml = simplexml_load_string($casResponse);
	$response = array();
	if ($xml->authenticationSuccess) {
		$login = (string) $xml->authenticationSuccess[0]->user[0];
		$_SESSION['login'] = $login;
		$response['success'] = $login;
		$response['session_id'] = session_id();
		newPlayer($login);
	}
	if ($xml->authenticationFailure) {
		$response['failure'] = (string) $xml->authenticationFailure[0]->attributes()['code'];
	}
	return $response;
}

/*
 * Input:
 * - login: login du joueur
 *
 * Output:
 * - player_id: identifiant du joueur correspondant, false si le joueur n'existe pas
 */
function getIdForPlayer ($login) {
	global $bdd;
	$player_id = false;
	$req = $bdd->prepare('
		SELECT id_joueur
		FROM joueurs
		WHERE login = ?;
	');
	$req->execute(array($login));
	if ($row = $req->fetch()) {
		$player_id = $row['id_joueur'];
	}
	return $player_id;
}

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
 * -date_debut: Y-m-d H:i:s
 * -date_fin: Y-m-d H:i:s
 * -password: chaine de caracteres ('NULL' si non renseigne)
 * Output:
 * -booleen: true si tout se passe bien, false sinon
 */
function newGame ($nom, $date_debut, $date_fin, $password) {
	global $bdd;
	if (($date_debut<$date_fin)&&($date_debut >= date('Y-m-d H:i:s', time()))) {
		if ($password == 'NULL') {
			$req = $bdd->prepare('
				INSERT INTO parties (nom, date_debut, date_fin) 
				VALUES (:nom, :date_debut, :date_fin)');
			$req->execute(array(
				'nom' => $nom,
				'date_debut' => $date_debut,
				'date_fin' => $date_fin
			));
		} else {
			$req = $bdd->prepare('
				INSERT INTO parties (nom, date_debut, date_fin, password)
				VALUES (:nom, :date_debut, :date_fin, :password)
			');
			$req->execute(array(
				'nom' => $nom,
				'date_debut' => $date_debut,
				'date_fin' => $date_fin,
				'password' => sha1($password)
			));
		}
		$req = $bdd->prepare('
			SELECT *
			FROM parties
			WHERE id_partie = LAST_INSERT_ID()
			LIMIT 1;
		');
		$req->execute();
		$newGame = false;
		if ($row = $req->fetch()) {
			$newGame = array(
				'id_partie' => $row['id_partie'],
				'nom' => $row['nom'],
				'date_debut' => $row['date_debut'],
				'date_fin' => $row['date_fin'],
				'partie_privee' => $row['password'] === NULL ? 'NO' : 'YES'
			);
		}
		return $newGame;
	} else
		return false;
}

/*
 * Input:
 * - date_insc: date d'inscription du joueur (DATETIME)
 * - partie: ID de la partie a laquelle s'inscrire
 * - index_equipe: index de l'equipe dans laquelle s'inscrire
 * - joueur: ID du joueur a inscrire
 * - password: chaine de caracteres ('NULL' si non renseigne)
 *
 * Output:
 * -booleen: true si tout se passe bien, false sinon
 */

function joinGame ($date_insc, $partie, $index_equipe, $joueur, $password) {
	global $bdd;
	
	$equipe = $index_equipe + 1;

	//verif : la partie a laquelle on s'inscrit doit exister
	$verif = $bdd->prepare('
		SELECT COUNT(id_partie)
		FROM parties 
		WHERE id_partie = :partie');
	$verif->execute(array(
				'partie' => $partie
			));
	$nb = $verif->fetchColumn();	//nombre de parties correspondant a l'ID entre (0 ou 1)

	//verif2 : le joueur qu'on veut inscrire doit exister
	$verif2 = $bdd->prepare('
		SELECT COUNT(id_joueur)
		FROM joueurs 
		WHERE id_joueur = :joueur');
	$verif2->execute(array(
				'joueur' => $joueur
			));
	$nb2 = $verif2->fetchColumn();	//nombre de joueurs correspondant a l'ID entre (0 ou 1)

	/*//verif3 : le joueur ne doit pas deja etre inscrit a la partie
	$verif3 = $bdd->prepare('
		SELECT COUNT(id_inscription)
		FROM inscriptions 
		WHERE partie = :partie
		AND joueur = :joueur');
	$verif3->execute(array(
				'partie' => $partie,
				'joueur' => $joueur
			));
	$nb3 = $verif3->fetchColumn();	//nombre de fois où le joueur s'est inscrit a la partie (0 ou 1)*/
	
	if (($nb != 0)&&($equipe >= 1)&&($equipe <= 4)&&($nb2 != 0)/*&&($nb3 != 1)*/)
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
				return false;
			}
		while ($row = $req->fetch()) 
		{
			//verification de la correspondance du mot de passe
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
							return false;
						}
					return true;
				}
		}
	}
	return false;
}


/*
 * Input:
 * - joueurID: identifiant du joueur
 *
 * Output:
 * - partieID: identifiant de la partie a laquelle est actuellement inscrit le joueur
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


/*
 * Output:
 * - partiesActives : tableau contenant toutes les parties non terminees
 * (id_partie, nom, password, date_debut, date_fin)
 */
function getActiveGamesList () {
	global $bdd;
	$req = $bdd->prepare('
		SELECT *
		FROM parties
		WHERE date_debut <= NOW()
			AND date_fin >= NOW()
		ORDER BY id_partie;
	');
	$req->execute();
	$list = array();
	while ($row = $req->fetch()) {
		$list[] = array(
			'id_partie' => $row['id_partie'],
			'nom' => $row['nom'],
			'date_debut' => $row['date_debut'],
			'date_fin' => $row['date_fin'],
			'partie_privee' => $row['password'] === NULL ? 'NO' : 'YES'
		);
	}
	return $list;
}

/*
 * Input:
 * - nbParties: nombre de parties qu'on veut afficher dans l'historique
 * Output:
 * - historique des "nbParties" parties : tableau contenant les "nbParties" dernières parties terminees
 */
function getHistoriqueParties ($nbParties) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT *
		FROM parties
		WHERE date_fin <= NOW()
		ORDER BY date_fin DESC
		LIMIT :nbParties
	');
	$req->execute(array(
		'nbParties' => $nbParties
	));
	$list = array();
	while ($row = $req->fetch()) {
		$list[] = array(
			'id_partie' => $row['id_partie'],
			'nom' => $row['nom'],
			'date_debut' => $row['date_debut'],
			'date_fin' => $row['date_fin']
		);
	}
	return $list;
}

/*
 * Input:
 * - id_partie: identifiant de la partie active (récupérée avec getPartieActiveJoueur)
 * - id_joueur: identifiant du joueur
 *
 * Output:
 * - equipe : numero de l'équipe dans laquelle est actuellement inscrit le joueur 
 */


function getEquipeJoueurPartieActive ($id_partie, $id_joueur) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT equipe
		FROM inscriptions
		WHERE joueur = :joueur
		AND partie = :partie
		ORDER BY date_inscription DESC
		LIMIT 1');
	$req->execute(array(
		'joueur' => $id_joueur,
		'partie' => $id_partie
	));
	if ($row = $req->fetch()) {
		return $row['equipe'];
	} else {
		return 0;
	}

}


/*
 * Input:
 * - id_partie: identifiant de la partie dont on veut la liste des joueurs
 *
 * Output:
 * - joueurs : retourne un tableau contenant la liste des joueurs de la partie id_partie : id du joueur, login, et id equipe
 */


function getListeJoueursPartie ($id_partie) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT i.joueur, j.login
		FROM inscriptions i, joueurs j
		WHERE i.joueur=j.id_joueur and partie = :id_partie');
	$req->execute(array(
		'id_partie' => $id_partie
	));
	if ($row = $req->fetch()) {
		return $row;
	} else {
		return 0;
	}
	/*ici on affiche tous les joueurs s'étant inscrit à la partie, y compris ceux qui s'en sont désinscrit.
	l'affichage est donc à limiter : 

	while ($row = $req->fetch()) {
		if ((getPartieActiveJoueur($row[0]) == $_POST["id_partie"])&&($temp != $row[0]))	//si le joueur est encore inscrit à la partie
		{																					//$temp sert à ne pas répéter le nom du joueur
			echo $row[0];
			echo $row[1];
			$temp = $row[0];
		}
	}*/

}


/*
 * Input:
 * - id_partie: id de la partie à laquelle on est inscrit
 *
 * Output:
 * - date_debut: date_debut de la partie (DATETIME)
 */
function getDateDebutPartie ($id_partie) {
	global $bdd;
	$req = $bdd->prepare('
	SELECT date_debut
	FROM parties
	WHERE id_partie = :id_partie');
	$req->execute(array(
			'id_partie' => $_POST["id_partie"]
		));
	while ($row = $req->fetch()) {
		$date_debut = $row[0];
	}
	$date_debut2 = new DateTime(trim($date_debut));		//création de l'objet DATETIME
	return $date_debut2;
}

/*
 * Input:
 * - id_partie: id de la partie à laquelle on est inscrit
 *
 * Output:
 * - tempRestant: temps restant avant la fin de la partie
 */
function getTempsRestantPartie ($id_partie) {
	global $bdd;
	$req = $bdd->prepare('
	SELECT date_fin
	FROM parties
	WHERE id_partie = :id_partie');
	$req->execute(array(
			'id_partie' => $id_partie
		));
	while ($row = $req->fetch()) {
		$date_fin = $row[0];
	}
	$date_fin2 = new DateTime(trim($date_fin));		//création de l'objet DATETIME
	$time = new DateTimeZone("Europe/Paris"); 
	$now = new DateTime(date("Y-m-d H:i:s"), $time);
	$tempsRestant = $now->diff($date_fin2);	
	return $tempsRestant;
	/* l'affichage se fera comme suit : 
	echo $tempsRestant->format('%d jours %h heures %i minutes %s secondes');
	*/
}

?>