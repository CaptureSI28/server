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
 * - partie: identifiant de la partie
 * - qrcode: identifiant du qrcode flashé
 *
 * Output:
 * -booleen: true si le qrcode est ouvert, false sinon (false si le qrcode a été flashé dans les 5 minutes précédentes)
 */
function qrcodeOuvert ($partie, $qrcode, $date) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT TIME_TO_SEC(TIMEDIFF(NOW(),date_flash)) as delai
		FROM infos_flashs
		WHERE partie = :partie
		AND qrcode = :qrcode
		ORDER BY date_flash DESC
		LIMIT 1');
	$req->execute(array(
		'partie' => $partie,
		'qrcode' => $qrcode
	));
	if ($row = $req->fetch())
		$delai=$row['delai'];		//récupération de la date du dernier flash de ce qrcode 
	else
		return true;
	//if($delai < (5*60))		//si le temps écoulé entre maintenant et le dernier flash est inférieur à 5 minutes, alors le QRcode est fermé
	if($delai < (0*60))	//pour les tests
		return false;
	else 
		return true;
}

/*
 * Input:
 * -date: date du flash DATETIME
 * -joueur: id du joueur qui flash
 * -qrcode: id du qrcode flashé
 *
 * Output:
 * -booleen: true si tout se passe bien, false sinon, l'équipe gagne autant de points par flash que ce qu'elle possède de zones (1 zone = 1 point par flash, 2 zones = 2 points par flash etc.)
 */
function newFlash ($date, $id_joueur, $qrcode) {
	global $bdd;
	$partieActiveJoueur = getPartieActiveJoueur ($id_joueur);
	if(qrcodeOuvert($partieActiveJoueur, $qrcode, $date) == true)			//si le qrcode est ouvert
		{
			$equipe = getEquipeJoueurPartieActive ($partieActiveJoueur, $id_joueur);
			$nbZones = getNombreZonesEquipePartie($partieActiveJoueur, $equipe);
			$nbPoints=pow(2,$nbZones);	
			try {
			$req = $bdd->prepare('
					INSERT INTO flashs (date_flash, joueur, qrcode, nbpoints) 
					VALUES (:date, :id_joueur, :qrcode, :nbPoints)');
				$req->execute(array(
					'date' => $date,
					'id_joueur' => $id_joueur,
					'qrcode' => $qrcode,
					'nbPoints' => $nbPoints
				));
			} catch (Exception $e) {
				return false;
			}
			return true;
		}
	else 					//si le qrcode est fermé
		return false;
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
		$attributes = $xml->authenticationFailure[0]->attributes();
		$response['failure'] = (string) $attributes['code'];
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
		WHERE login = :login;
	');
	$req->execute(array('login' => $login));
	if ($row = $req->fetch()) {
		$player_id = $row['id_joueur'];
	}
	return $player_id;
}

/*
 * Input:
 * - id: id du joueur
 *
 * Output:
 * - player_login: login du joueur correspondant, false si le joueur n'existe pas
 */
function getLoginForPlayer ($id_joueur) {
	global $bdd;
	$player_login = false;
	$req = $bdd->prepare('
		SELECT login
		FROM joueurs
		WHERE id_joueur = :id_joueur;
	');
	$req->execute(array('id_joueur' => $id_joueur));
	if ($row = $req->fetch()) {
		$player_login = $row['login'];
	}
	return $player_login;
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
 * -booleen: un tableau contenant les infos de la nouvelle partie si tout se passe bien, false sinon
 */
function newGame ($nom, $date_debut, $date_fin, $password) {
	global $bdd;
	$createur = getIdForPlayer($_SESSION['login']);
	if (($date_debut<$date_fin)&&($date_debut < date('Y-m-d H:i:s', time())))		//si la date de début est antérieure à la date actuelle,
		$date_debut = date('Y-m-d H:i:s', time());									//on ramène la date de début à celle actuelle
	if (($date_debut<$date_fin)&&($date_debut >= date('Y-m-d H:i:s', time()))) {
		if ($password == NULL || $password == '') {
			$req = $bdd->prepare('
				INSERT INTO parties (nom, createur, date_debut, date_fin) 
				VALUES (:nom, :createur, :date_debut, :date_fin)');
			$req->execute(array(
				'nom' => $nom,
				'createur' => $createur,
				'date_debut' => $date_debut,
				'date_fin' => $date_fin
			));
		} else {
			$req = $bdd->prepare('
				INSERT INTO parties (nom, createur, date_debut, date_fin, password)
				VALUES (:nom, :createur, :date_debut, :date_fin, :password)
			');
			$req->execute(array(
				'nom' => $nom,
				'createur' => $createur,
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
				'createur' => $row['createur'],
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
 * - id_partie : id de la partie
 * - id_joueur : id du joueur
 *
 * Output:
 * -result: 0 si le joueur s'inscrit à la partie pour la première fois ; 1,2,3,4 correspondant à l'équipe dans laquelle était le joueur la dernière fois
 */

function equipeAncienneInscriptionPartie ($id_partie,$id_joueur) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT equipe
		FROM inscriptions
		WHERE partie = :id_partie
		AND joueur = :id_joueur
		LIMIT 1');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_joueur' => $id_joueur
	));
	$result=0;
	if ($row = $req->fetch()) {
		$result=$row[0];
	}
	return $result;
	
}

/*
 * Input:
 * - date_insc: date d'inscription du joueur (DATETIME)
 * - partie: ID de la partie a laquelle s'inscrire
 * - index_equipe: index de l'equipe dans laquelle s'inscrire (0 -> 3)
 * - joueur: ID du joueur a inscrire
 * - password: chaine de caracteres ('NULL' si non renseigne)
 *
 * Output:
 * -booleen: true si tout se passe bien, false sinon
 */

function joinGame ($date_insc, $partie, $index_equipe, $joueur, $password) {
	global $bdd;
	$result = false;
	
	$ancienneEquipe = equipeAncienneInscriptionPartie ($partie,$joueur);
	if($ancienneEquipe!=0)
		$equipe=$ancienneEquipe;
	else
		$equipe=$index_equipe;

	// Verif : la partie existe
	$verif = $bdd->prepare('
		SELECT id_partie, password
		FROM parties 
		WHERE id_partie = :partie');
	$verif->execute(array(
				'partie' => $partie
			));
	if ($row = $verif->fetch()) {
		// Verif : Le mot de passe est correct
		if(sha1($password) == $row['password'] || $row['password'] === NULL) {
			// Verif : Le joueur existe
			$verif2 = $bdd->prepare('
				SELECT id_joueur
				FROM joueurs 
				WHERE id_joueur = :joueur');
			$verif2->execute(array(
					'joueur' => $joueur
			));
			if ($row2 = $verif2->fetch()) {
				try {
					// Insertion
					$req = $bdd->prepare('
						INSERT INTO inscriptions (date_inscription, partie, equipe, joueur) 
						VALUES (:date_insc, :partie, :equipe, :joueur)');
					$req->execute(array(
						'date_insc' => $date_insc,
						'partie' => $partie,
						'equipe' => $equipe,
						'joueur' => $joueur
					));
					$result = true;
				} catch (Exception $e) {
					return false;
				}
			}
		}
	}
	
	return $result;
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
 * (id_partie, nom, createur, password, date_debut, date_fin)
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
			'createur' => getLoginForPlayer($row['createur']),
			'date_debut' => $row['date_debut'],
			'date_fin' => $row['date_fin'],
			'partie_privee' => ($row['password'] === sha1('') || $row['password'] === NULL) ? 'NO' : 'YES',
			'players' => getListeJoueursActifsPartie($row['id_partie'])
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
 * - joueurs : retourne un tableau contenant la liste des joueurs de la partie id_partie : id du joueur, login et id de l'équipe
 */
function getListeJoueursActifsPartie ($id_partie) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT k.joueur, k.equipe, j.login
		FROM (
			SELECT *
			FROM( 
				SELECT *
				FROM inscriptions
				ORDER BY date_inscription DESC) i
			GROUP BY i.joueur) k, joueurs j
		WHERE k.joueur=j.id_joueur AND k.partie=:id_partie');
	$req->execute(array(
		'id_partie' => $id_partie
	));
	$list = array();
	while ($row = $req->fetch()) {
		$list[] = array(
			'id_joueur' => $row['joueur'],
			'login' => $row['login'],
			'equipe' => $row['equipe']
		);
	}
	return $list;
}



/*
 * Input:
 * - id_partie: identifiant de la partie dont on veut la liste des joueurs
 *
 * Output:
 * - joueurs : retourne un tableau contenant la liste des joueurs de la partie id_partie : id du joueur, login et id de l'équipe
 */
function getListeJoueursActifsPartieEquipe ($id_partie, $id_equipe) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT k.joueur, k.equipe, j.login
		FROM (
			SELECT *
			FROM( 
				SELECT *
				FROM inscriptions
				ORDER BY date_inscription DESC) i
			GROUP BY i.joueur) k, joueurs j
		WHERE k.joueur=j.id_joueur AND k.partie=:id_partie AND k.equipe=:id_equipe');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_equipe' => $id_equipe
	));
	$list = array();
	while ($row = $req->fetch()) {
		$list[] = array(
			'id_joueur' => $row['joueur'],
			'login' => $row['login'],
			'equipe' => $row['equipe']
		);
	}
	return $list;
}

/*
 * Input:
 * - id_partie: identifiant de la partie dont on veut la liste des joueurs
 *
 * Output:
 * - joueurs : retourne un tableau contenant le nombre de joueurs de la partie id_partie par équipe : equipe, nbJoueurs
 */
function getNombreJoueursActifsPartieEquipes ($id_partie) {
	$nombreTab = array();
	for ($i=1;$i<=4;$i++) {
		$nombreTab[] = array(
			'equipe' => $i,
			'nbJoueurs' => getNombreJoueursActifsPartieEquipe($id_partie, $i)
		);
	}
	return $nombreTab;
}

/*
 * Input:
 * - partie: identifiant de la partie
 * - equipe: identifiant de l'équipe
 *
 * Output:
 * - nbJoueurs: nombre de joueurs de l'équipe
 */
function getNombreJoueursActifsPartieEquipe ($id_partie, $id_equipe) {
	global $bdd;
	$joueurs = getListeJoueursActifsPartieEquipe($id_partie, $id_equipe);
	return count($joueurs);
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_equipe : identifiant de l'équipe
 *
 * Output:
 * - nb : nombre de flashs de l'équipe dans une partie
 */
function getNombreFlashsEquipePartie ($id_partie, $id_equipe) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT COUNT(id_flash) AS nbFlashs
		FROM infos_flashs
		WHERE partie = :id_partie
		AND equipe = :id_equipe');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_equipe' => $id_equipe
	));
	if ($row = $req->fetch())
		return $row['nbFlashs'];
}

/*
* Input:
* -id_partie : identifiant de la partie
* -id_zone: identifiant de la zone
*
* Output:
* Score actuel d'une équipe dans une partie = somme des points gagnés grâce à un flash
*/
function getScoreEquipePartie ($id_partie, $id_equipe) {
	global $bdd;
	$score = 0;
	$req = $bdd->prepare('
		SELECT SUM(nbpoints) as score
		FROM infos_flashs
		WHERE equipe=:id_equipe
		AND partie = :id_partie;');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_equipe' => $id_equipe
	));
	if ($row = $req->fetch()) {
		$score = $row['score'];
	}
	if ($score == null) $score=0;
	return $score;	
}

/*
* Input:
* -id_partie : identifiant de la partie
*
* Output:
* TABLEAU contenant les scores actuels de chaque équipe dans une partie = somme des points gagnés grâce à un flash
*/
function getScoreEquipesPartie ($id_partie) {
	global $bdd;
	$scores = array();
	for($i=1; $i<=4; $i++) {
		$scores[] = array(
			'equipe' => $i,
			'score' => getScoreEquipePartie($id_partie, $i)
		);
	}
	return $scores;
}

/*
 * Input:
 * - id_partie: identifiant de la partie
 * - id_joueur : identifiant du joueur
 *
 * Output:
 * - score : score du joueur (chaque flash rapporte autant de points que le nombre de zones capturées par l'équipe à ce moment)
 */
function getScoreJoueurPartie ($id_partie, $id_joueur) {
	global $bdd;
	$req = $bdd->prepare('		
		SELECT SUM(nbpoints) AS score
		FROM infos_flashs
		WHERE partie = :id_partie
		AND joueur = :id_joueur');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_joueur' => $id_joueur
	));
	if ($row = $req->fetch()) {
		$result = $row['score'];
	}
	if($result == NULL)
		return 0;
	else
		return $result;
}

/*
 * Input:
 * - id_partie: identifiant de la partie
 * - id_joueur : identifiant du joueur
 *
 * Output:
 * - timePlayed : temps joue en secondes
 */
function getTimePlayed ($id_partie, $id_joueur) {
	global $bdd;
	$req = $bdd->prepare('		
		SELECT (
			SELECT TIME_TO_SEC(TIMEDIFF(NOW(),(
				SELECT date_inscription
				FROM inscriptions
				WHERE partie = :id_partie
					AND joueur = :id_joueur
				ORDER BY date_inscription
				LIMIT 1
			)))
		) as time_played
	');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_joueur' => $id_joueur
	));
	$timePlayed = 0;
	if ($row = $req->fetch()) {
		$timePlayed = $row['time_played'];
	}
	return $timePlayed;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_equipe : identifiant de l'équipe
 * - id_zone : numéro de la zone
 *
 * Output:
 * - nb : nombre de flashs de l'équipe sur une zone dans une partie
 */
function getNombreFlashsEquipeZonePartie ($id_partie, $id_equipe, $id_zone) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT COUNT(id_flash) AS nbFlashs
		FROM infos_flashs
		WHERE partie = :id_partie
		AND equipe = :id_equipe
		AND zone = :id_zone');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_equipe' => $id_equipe,
		'id_zone' => $id_zone
	));
	if ($row = $req->fetch())
		return $row['nbFlashs'];
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_zone : numéro de la zone
 *
 * Output:
 * - meilleuresEquipes : TABLEAU contenant l'identifiant de l'équipe (ou des équipes ex-aequo) qui détient la zone (qui a le plus flashé cette zone)
 */
function getMeilleuresEquipesZone ($id_partie, $id_zone) {
	global $bdd;
	$max=0;	
	$compteur=0;
	$meilleuresEquipes = array();
	for ($i=1;$i<=4;$i++)
		{
			if ($max < getNombreFlashsEquipeZonePartie ($id_partie, $i, $id_zone))
				$max = getNombreFlashsEquipeZonePartie ($id_partie, $i, $id_zone);		
		}
	for ($i=1;$i<=4;$i++)
		{
			if ($max == getNombreFlashsEquipeZonePartie ($id_partie, $i, $id_zone))
				{
					$meilleuresEquipes[$compteur]=$i;
					$compteur++;
				}
		}
	return $meilleuresEquipes;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_zone : identifiant de la zone
 *
 * Output:
 * - meilleuresEquipes : identifiant de l'équipe qui a flashé le plus de fois une zone, en cas d'égalité, renvoie l'équipe qui a flashé en dernier la zone
 */
function getMeilleureEquipeZone ($id_partie, $id_zone) {
	global $bdd;
	$meilleureEquipe = 0;
	$req = $bdd->prepare('
		SELECT equipe, MAX(date_flash) as date_flash, COUNT(id_flash) as nb_flashs
		FROM infos_flashs
		WHERE partie=:id_partie AND zone=:id_zone
		GROUP BY equipe
		ORDER BY nb_flashs DESC, date_flash DESC
		LIMIT 1');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_zone' => $id_zone
	));
	if ($row = $req->fetch())
		$meilleureEquipe = $row['equipe'];
	return($meilleureEquipe);
}

/*
* Output:
* - $nbzones : nombre de zones dans le jeu
*/
function getNombreZones() {
	global $bdd;
	$req = $bdd->prepare('
		SELECT COUNT(id_zone)
		FROM zones;
	');
	$req->execute();
	if ($row = $req->fetch())
		$nbzones = $row[0];
	return $nbzones;
}


/*
* Input:
* - id_partie : identifiant de la partie
* - id_equipe : identifiant de l'équipe
*
* Output:
* - nbZonesEquipes : nombre de zones actuellement détenues par une équipe
*/
function getNombreZonesEquipePartie ($id_partie, $id_equipe) {
	$nbTotalZones = getNombreZones();
	$nbZonesEquipe = 0;
	for ($i=1; $i<=$nbTotalZones; $i++) {
		$meilleureEquipe = getMeilleureEquipeZone($id_partie, $i);
		if($meilleureEquipe != 0 && $meilleureEquipe==$id_equipe) {
			$nbZonesEquipe++;
		}
	}
	return $nbZonesEquipe;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 *
 * Output:
 * - array: tableau des équipes avec leur score (clé : équipe_numéro, valeur : score)
 */
function getClassementEquipesPartie ($id_partie) {
	global $bdd;
	$array = array(
    "Equipe 1" => getScoreEquipePartie($id_partie, 1),
    "Equipe 2" => getScoreEquipePartie($id_partie, 2),
    "Equipe 3" => getScoreEquipePartie($id_partie, 3),
    "Equipe 4" => getScoreEquipePartie($id_partie, 4)
	);
	arsort($array);
	return $array;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_zone : identifiant de la zone
 *
 * Output:
 * - array: tableau des équipes avec leur score sur une zone (clé : équipe_numéro, valeur : score)
 */
function getClassementEquipesZonePartie ($id_partie, $id_zone) {
	global $bdd;
	$array = array(
    "Equipe 1" => getNombreFlashsEquipeZonePartie ($id_partie, 1, $id_zone),
    "Equipe 2" => getNombreFlashsEquipeZonePartie ($id_partie, 2, $id_zone),
    "Equipe 3" => getNombreFlashsEquipeZonePartie ($id_partie, 3, $id_zone),
    "Equipe 4" => getNombreFlashsEquipeZonePartie ($id_partie, 4, $id_zone)
	);
	arsort($array);
	return $array;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_zone : numéro de la zone
 *
 * Output:
 * - couleurZone : couleur de l'équipe qui détient la zone (qui a le plus flashé cette zone et a flashé en dernier)
 */
function getCouleurZone ($id_partie, $id_zone) {
	global $bdd;
	$meilleureEquipe=getMeilleureEquipeZone($id_partie, $id_zone);
	if($meilleureEquipe != 0) {
		$couleurZone = getCouleurEquipe($meilleureEquipe);
	}
	else {
		$couleurZone = 0;
	}
	return $couleurZone;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 *
 * Output:
 * - couleurZone : TABLEAU contenant l'identifiant de chaque zone ainsi que la couleur de l'équipe qui détient la zone (qui a le plus flashé cette zone et a flashé en dernier)
 */
function getCouleursZones ($id_partie) {
	$nbTotalZones = getNombreZones();
	$couleursTab = array();
	for ($zone=1; $zone<=$nbTotalZones; $zone++) {
		$meilleureEquipe = getMeilleureEquipeZone($id_partie, $zone);
			$couleursTab[] = array(
				'zone' => $zone,
				'couleur' => getCouleurEquipe($meilleureEquipe)
		);
	}
	return $couleursTab;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 *
 * Output:
 * - couleurZone : TABLEAU contenant l'identifiant de chaque zone ainsi que l'identifiant de l'équipe qui détient la zone (qui a le plus flashé cette zone et a flashé en dernier)
 */
function getIdEquipeParZone ($id_partie) {
	$nbTotalZones = getNombreZones();
	$equipesZonesTab = array();
	for ($zone=1; $zone<=$nbTotalZones; $zone++) {
		$equipesZonesTab[] = array(
			'zone' => $zone,
			'equipe' => getMeilleureEquipeZone($id_partie, $zone)
		);
	}
	return $equipesZonesTab;
}

/*
 * Input:
 * - id_equipe : identifiant de l'équipe
 *
 * Output:
 * - couleurEquipe : couleur de l'équipe 
 */
function getCouleurEquipe($id_equipe){
	global $bdd;
	$req = $bdd->prepare('
		SELECT hexcolor
		FROM equipes
		WHERE id_equipe = :id_equipe');
	$req->execute(array(
		'id_equipe' => $id_equipe
	));
	if ($row = $req->fetch()) {
		return $row['hexcolor'];
	} else {
		return NULL;
	}
}



/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_joueur : identifiant du joueur
 *
 * Output:
 * - nb : nombre de flashs d'un joueur dans une partie
 */
function getNombreFlashsJoueurPartie ($id_partie, $id_joueur) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT COUNT(id_flash) AS nbFlashs
		FROM infos_flashs
		WHERE partie = :id_partie
		AND joueur = :id_joueur');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_joueur' => $id_joueur
	));
	if ($row = $req->fetch())
		return $row['nbFlashs'];
}

/*
 * Input:
 * - id_joueur : identifiant du joueur
 *
 * Output:
 * - nb : nombre total des flashs du joueur
 */
function getNombreFlashsJoueur ($id_joueur) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT COUNT(id_flash) AS nbFlashs
		FROM infos_flashs
		WHERE joueur = :id_joueur');
	$req->execute(array(
		'id_joueur' => $id_joueur
	));
	if ($row = $req->fetch())
		return $row['nbFlashs'];
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_joueur : identifiant du joueur
 * - id_qrcode : numéro du QRcode concerné
 *
 * Output:
 * - nb : nombre de flashs d'un QRcode du joueur 
 */
function getNombreFlashsJoueurQRCodePartie ($id_partie, $id_joueur, $id_qrcode) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT COUNT(id_flash) AS nbFlashs
		FROM infos_flashs
		WHERE partie = :id_partie
		AND joueur = :id_joueur
		AND qrcode = :id_qrcode');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_joueur' => $id_joueur,
		'id_qrcode' => $id_qrcode
	));
	if ($row = $req->fetch())
		return $row['nbFlashs'];
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 *
 * Output:
 * - meilleursFlasheurs : TABLEAU contenant le (ou les) login du (ou des) joueur qui a fait le plus de flashs dans la partie
 */
function getMeilleurFlasheurPartie ($id_partie) {
	global $bdd;
	$row = getListeJoueursActifsPartie($id_partie);
	$nbFlashsMax = 0;
	$compteur=0;
	$meilleursFlasheurs = array();
	foreach($row as $joueur)
		{
			if ($nbFlashsMax < getNombreFlashsJoueur($joueur["id_joueur"]))
				$nbFlashsMax = getNombreFlashsJoueur($joueur["id_joueur"]);
		}
	foreach($row as $joueur)
		{
			if ($nbFlashsMax == getNombreFlashsJoueur($joueur["id_joueur"]))
				{
					$meilleursFlasheurs[$compteur]=$joueur["login"];
					$compteur++;
				}
		}
	return $meilleursFlasheurs;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_equipe : identifiant de l'équipe
 *
 * Output:
 * - meilleursFlasheurs : TABLEAU contenant le (ou les) login du (ou des) joueur qui a fait le plus de flashs dans la partie et dans l'équipe
 */
function getMeilleurFlasheurEquipePartie ($id_partie, $id_equipe) {
	global $bdd;
	$row = getListeJoueursActifsPartie($id_partie);
	$nbFlashsMax = 0;
	$compteur=0;
	$meilleursFlasheurs = array();
	foreach($row as $joueur)
		{
			if (($id_equipe == $joueur["equipe"])&&($nbFlashsMax < getNombreFlashsJoueur($joueur["id_joueur"])))
				$nbFlashsMax = getNombreFlashsJoueur($joueur["id_joueur"]);
		}
	foreach($row as $joueur)
		{
			if (($id_equipe == $joueur["equipe"])&&($nbFlashsMax == getNombreFlashsJoueur($joueur["id_joueur"])))
				{
					$meilleursFlasheurs[$compteur]=$joueur["login"];
					$compteur++;
				}
		}
	return $meilleursFlasheurs;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_qrcode : identifiant du qrcode
 *
 * Output:
 * - meilleursFlasheurs : TABLEAU contenant le (ou les) login du (ou des) joueur qui a fait le plus de flashs dans la partie et dans l'équipe
 */
function getMeilleurFlasheurQRCodePartie ($id_partie, $id_qrcode) {
	global $bdd;
	$row = getListeJoueursActifsPartie($id_partie);
	$nbFlashsMax = 0;
	$compteur=0;
	$meilleursFlasheurs = array();
	foreach($row as $joueur)
		{
			if ($nbFlashsMax < getNombreFlashsJoueurQRCodePartie($id_partie, $joueur["id_joueur"], $id_qrcode))
				$nbFlashsMax = getNombreFlashsJoueurQRCodePartie($id_partie, $joueur["id_joueur"], $id_qrcode);
		}
	foreach($row as $joueur)
		{
			if ($nbFlashsMax == getNombreFlashsJoueurQRCodePartie($id_partie, $joueur["id_joueur"], $id_qrcode))
				{
					$meilleursFlasheurs[$compteur]=$joueur["login"];
					$compteur++;
				}
		}
	return $meilleursFlasheurs;
}

/*
 * Input:
 * - gameId : identifiant de la partie
 *
 * Output:
 * - rankings: tableau d'objets json contenant le nom du joueur 'name' et le score 'score'
 */
function getOverallRankings ($gameId) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT (
			SELECT login
			FROM joueurs
			WHERE id_joueur = i.joueur
			LIMIT 1
		) AS name, i.joueur as joueur, count(*) AS score
		FROM infos_flashs i
		WHERE partie = :game_id
		GROUP BY joueur, name
		ORDER BY score DESC;
	');
	$req->execute(array(
		'game_id' => $gameId
	));
	$rankings = array();
	while ($row = $req->fetch()) {
		$rankings[] = array(
			'name' => $row['name'],
			'score' => strval($row['score']),
			'team' => intval(getEquipeJoueurPartieActive($gameId, $row['joueur']))
		);
	}
	return $rankings;
}

/*
 * Input:
 * - gameId : identifiant de la partie
 *
 * Output:
 * - rankings: tableau d'objets json contenant le nom du joueur 'name' et le score 'score'
 */
function getTeamRankings ($gameId) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT (
			SELECT login
			FROM joueurs
			WHERE id_joueur = i.joueur
			LIMIT 1
		) AS name, i.joueur as joueur, sum(nbpoints) AS score
		FROM infos_flashs i
		WHERE partie = :game_id
		GROUP BY joueur, name
		ORDER BY score DESC;
	');
	$req->execute(array(
		'game_id' => $gameId
	));
	$rankings = array();
	while ($row = $req->fetch()) {
		$rankings[] = array(
			'name' => $row['name'],
			'score' => strval($row['score']),
			'team' => intval(getEquipeJoueurPartieActive($gameId, $row['joueur']))
		);
	}
	return $rankings;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 *
 * Output:
 * - array: tableau des joueurs avec leur score (clé : id_joueur, valeur : score)
 */
function getClassementJoueursPartie ($id_partie) {
	global $bdd;
	$array = array();
	$listeJoueursPartie=getListeJoueursActifsPartie($id_partie);
	foreach($listeJoueursPartie as $value)
		{
			$array["".$value['login']] = getNombreFlashsJoueurPartie($id_partie,$value['id_joueur']);
		}
	arsort($array);
	return $array;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_equipe : identifiant de l'équipe
 *
 * Output:
 * - array: tableau des joueurs dans l'équipe avec leur score (clé : id_joueur, valeur : score)
 */
function getClassementJoueursEquipePartie ($id_partie, $id_equipe) {
	global $bdd;
	$array = array();
	$listeJoueursPartieEquipe=getListeJoueursActifsPartieEquipe($id_partie, $id_equipe);
	foreach($listeJoueursPartieEquipe as $value)
		{
			$array["".$value['login']] = getNombreFlashsJoueurPartie($id_partie,$value['id_joueur']);
		}
	arsort($array);
	return $array;
}

/*
 * Input:
 * - id_partie : identifiant de la partie
 * - id_qrcode : identifiant du qrcode
 *
 * Output:
 * - array: tableau des joueurs avec leur score sur un QRCode (clé : id_joueur, valeur : score)
 */
function getClassementJoueursQRCodePartie ($id_partie, $id_qrcode) {
	global $bdd;
	$array = array();
	$listeJoueursPartie=getListeJoueursActifsPartie($id_partie);
	foreach($listeJoueursPartie as $value)
		{
			$array["".$value['login']] = getNombreFlashsJoueurQRCodePartie($id_partie,$value['id_joueur'],$id_qrcode);
		}
	arsort($array);
	return $array;
}

/*
 * Input:
 * - id_partie: identifiant de la partie
 *
 * Output:
 * - nbJoueurs : nombre de joueurs actifs dans la partie
 */
function getNbJoueursActifsPartie ($id_partie) {
	global $bdd;
	$joueurs = getListeJoueursActifsPartie ($id_partie);
	return count($joueurs);
}

/*
 * Input:
 * - id_partie: identifiant de la partie
 *
 * Output:
 * - derniersFlashs : tableau contenant les 50 derniers flashs de la partie
 */
function getDerniersFlashs ($id_partie) {
	global $bdd;
	$derniersFlashs=array();
	$req = $bdd->prepare('
		SELECT date_flash, qrcode, equipe, nbpoints, joueur, (
			SELECT login
			FROM joueurs
			WHERE id_joueur = i.joueur
		) as login
		FROM infos_flashs i
		WHERE partie = :id_partie		
		ORDER BY date_flash DESC');
	$req->execute(array(
		'id_partie' => $id_partie
	));
	while ($row = $req->fetch()) {
		$derniersFlashs[] = array(
			'date_flash' => $row['date_flash'],
			'qrcode' => $row['qrcode'],
			'equipe' => $row['equipe'],
			'joueur' => $row['joueur'],
			'nbpoints' => $row['nbpoints'],
			'login' => $row['login'],
			'zone' => getIdZoneByQrcode($row['qrcode'])
		);
	}
	return $derniersFlashs;
}

/*
* Input
* - qrcode : qrcode d'une zone
*
* Output
* - nom_zone : id de la zone à laquelle appartient le qrcode
*/
function getIdZoneByQrcode ($qrcode) {
	global $bdd;
	$id_zone = "";
	$req = $bdd->prepare('
		SELECT zone
		FROM qrcodes q
		WHERE q.id_qrcode = :qrcode');
	$req->execute(array(
		'qrcode' => $qrcode
	));
	if ($row = $req->fetch())
		$id_zone = $row['zone'];
	return $id_zone;
}



/*
* Input
* - qrcode : qrcode d'une zone
*
* Output
* - nom_zone : nom de la zone à laquelle appartient le qrcode
*/
function getNomZoneByQrcode ($qrcode) {
	global $bdd;
	$nom_zone = "";
	$req = $bdd->prepare('
		SELECT nom_zone
		FROM zones z, qrcodes q
		WHERE q.zone = z.id_zone AND q.id_qrcode = :qrcode');
	$req->execute(array(
		'qrcode' => $qrcode
	));
	if ($row = $req->fetch())
		$nom_zone = $row['nom_zone'];
	return $nom_zone;
}

/*
* Output
* - nom_zone : nom de la zone à laquelle appartient le qrcode
*/
function getZonesList () {
	global $bdd;
	$zones=array();
	$req = $bdd->prepare('
		SELECT id_zone, nom_zone
		FROM zones');
	$req->execute();
	while ($row = $req->fetch()) {
		$zones[] = array(
			'id_zone' => $row['id_zone'],
			'nom_zone' => $row['nom_zone']
		);
	}
	return $zones;
}

/*
 * Input:
 * - id_partie: identifiant de la partie
 * - id_equipe: identifiant de l'équipe
 *
 * Output:
 * - derniersFlashs : tableau contenant les 50 derniers flashs de la partie par l'équipe
 */
function getDerniersFlashsEquipe ($id_partie, $id_equipe) {
	global $bdd;
	$derniersFlashs=array();
	$req = $bdd->prepare('
		SELECT date_flash, qrcode, joueur
		FROM infos_flashs
		WHERE partie = :id_partie	
		AND equipe = :id_equipe	
		ORDER BY date_flash DESC');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_equipe' => $id_equipe
	));
	while ($row = $req->fetch()) {
		$derniersFlashs[] = array(
			'date_flash' => $row['date_flash'],
			'qrcode' => $row['qrcode'],
			'joueur' => $row['joueur']
		);
	}
	return $derniersFlashs;
}

/*
* Input:
* -id_partie : identifiant de la partie
*
* Output:
* TABLEAU contenant les derniers flashs de chaque équipe (chaque case du tableau est un tableau)
*/
function getDerniersFlashsEquipes ($id_partie) {
	global $bdd;
	$derniersFlashsEquipes = array(
    "1" => getDerniersFlashsEquipe($id_partie, 1),
    "2" => getDerniersFlashsEquipe($id_partie, 2),
    "3" => getDerniersFlashsEquipe($id_partie, 3),
    "4" => getDerniersFlashsEquipe($id_partie, 4)
	);
	return $derniersFlashsEquipes;
}

/*
 * Input:
 * - id_partie: identifiant de la partie
 * - id_joueur: identifiant du joueur
 *
 * Output:
 * - derniersFlashs : tableau contenant les 50 derniers flashs de la partie par le joueur
 */
function getDerniersFlashsJoueur ($id_partie, $id_joueur) {
	global $bdd;
	$derniersFlashs=array();
	$req = $bdd->prepare('
		SELECT date_flash, qrcode, equipe
		FROM infos_flashs
		WHERE partie = :id_partie	
		AND joueur = :id_joueur	
		ORDER BY date_flash DESC');
	$req->execute(array(
		'id_partie' => $id_partie,
		'id_joueur' => $id_joueur
	));
	while ($row = $req->fetch()) {
		$derniersFlashs[] = array(
			'date_flash' => $row['date_flash'],
			'qrcode' => $row['qrcode'],
			'equipe' => $row['equipe']
		);
	}
	return $derniersFlashs;
}


/*
 * Input:
 * - id_partie: identifiant de la partie
 *
 * Output:
 * - classementFlashs : tableau contenant le nb de flashs, la place, le login et l'équipe de chaque joueur de la partie
 */
function getClassementFlashs ($id_partie) {
	global $bdd;
	$listeJoueursPartie=getListeJoueursActifsPartie($id_partie);
	//tableau contenant id_joueur, login et equipe
	foreach($listeJoueursPartie as $value)
		{
			$classementFlashs[] = array(
			'score' => getNombreFlashsJoueurPartie($id_partie,$value['id_joueur']),
			'place' => 0,
			'login' => $value['login'],
			'team' => $value['equipe']
			);
		}
	arsort($classementFlashs);
	$i=1;
	foreach($classementFlashs as $value)
		{
			$value['place'] = $i;
			$i++;
		}
	return $classementFlashs;
}

/*
 * Input:
 * - id_partie: identifiant de la partie
 *
 * Output:
 * - classementPoints : tableau contenant le nb de points, la place, le login et l'équipe de chaque joueur de la partie
 */
function getClassementPoints ($id_partie) {
	global $bdd;
	$listeJoueursPartie=getListeJoueursActifsPartie($id_partie);
	//tableau contenant id_joueur, login et equipe
	foreach($listeJoueursPartie as $value)
		{
			$classementPoints[] = array(
			'score' => getScoreJoueurPartie($id_partie,$value['id_joueur']),
			'place' => 0,
			'login' => $value['login'],
			'team' => $value['equipe']
			);
		}
	arsort($classementPoints);
	$i=1;
	foreach($classementPoints as $value)
		{
			$value['place'] = $i;
			$i++;
		}
	return $classementPoints;
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