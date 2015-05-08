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
 * -booleen: true si tout se passe bien, false sinon, l'équipe gagne autant de points par flash que ce qu'elle possède de zones (1 zone = 1 point par flash, 2 zones = 2 points par flash etc.)
 */
function newFlash ($date, $id_joueur, $qrcode) {
	global $bdd;
	$partieActiveJoueur = getPartieActiveJoueur ($id_joueur);
	$equipe = getEquipeJoueurPartieActive ($partieActiveJoueur, $id_joueur);
	$nbZones = 1+getNombreZonesEquipePartie ($partieActiveJoueur, $equipe);
	echo "Nombre de points : ".$nbZones;
	try {
	$req = $bdd->prepare('
			INSERT INTO flashs (date_flash, joueur, qrcode, nbpoints) 
			VALUES (:date, :id_joueur, :qrcode, :nbPoints)');
		$req->execute(array(
			'date' => $date,
			'id_joueur' => $id_joueur,
			'qrcode' => $qrcode,
			'nbPoints' => $nbZones
		));
	} catch (Exception $e) {
		echo $e;
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
 * - index_equipe: index de l'equipe dans laquelle s'inscrire (0 -> 3)
 * - joueur: ID du joueur a inscrire
 * - password: chaine de caracteres ('NULL' si non renseigne)
 *
 * Output:
 * -booleen: true si tout se passe bien, false sinon
 */

function joinGame ($date_insc, $partie, $index_equipe, $joueur, $password) {
	global $bdd;
	
	$equipe = $index_equipe + 1; // $index_equipe (0 -> 3)

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
	global $bdd;
	$req = $bdd->prepare('
		SELECT k.equipe, COUNT(DISTINCT(k.joueur)) as nbJoueurs
		FROM (
			SELECT *
			FROM( 
				SELECT *
				FROM inscriptions
				ORDER BY date_inscription DESC) i
			GROUP BY i.joueur) k
		WHERE k.partie=:id_partie
        GROUP BY k.equipe
        ');
	$req->execute(array(
		'id_partie' => $id_partie
	));
	$list = array();
	while ($row = $req->fetch()) {
		$list[] = array(
			'equipe' => $row['equipe'],
			'nbJoueurs' => $row['nbJoueurs']
		);
	}
	return $list;
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
 * - id_partie: identifiant de la partie dont on veut la liste des joueurs
 *
 * Output:
 * - joueurs : retourne un tableau contenant la liste des joueurs de la partie id_partie : id du joueur, login et id de l'équipe
 */
 /*
function getListeJoueursActifsPartie ($id_partie) {
	global $bdd;
	$req = $bdd->prepare('
		SELECT i.joueur, j.login, i.equipe
		FROM inscriptions i, joueurs j
		WHERE i.joueur=j.id_joueur and partie = :id_partie
		ORDER BY equipe');			//l'ordre sert pour getNbJoueursEquipe
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
}*/


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
	$nb = $req->fetchColumn();
	return $nb;	
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
* -id_zone: identifiant de la zone
*
* Output:
* TABLEAU contenant les scores actuels de chaque équipe dans une partie = somme des points gagnés grâce à un flash
*/
function getScoreEquipesPartie ($id_partie) {
	global $bdd;
	$scores = array(
    "1" => getScoreEquipePartie($id_partie, 1),
    "2" => getScoreEquipePartie($id_partie, 2),
    "3" => getScoreEquipePartie($id_partie, 3),
    "4" => getScoreEquipePartie($id_partie, 4)
	);
	arsort($scores);
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
	$score=0;
	$req = $bdd->prepare('		
		SELECT SUM(nbpoints) as score
		FROM infos_flashs
		WHERE partie = :id_partie
		AND joueur = :id_joueur');
	$req->execute(array(
		'id_partie' => $id_partie,
		'joueur' => $id_joueur
	));
	$score = $req->fetchColumn();
	return $score;	
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
	$nb = $req->fetchColumn();
	return $nb;	
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
	$nbzones = $req->fetchColumn();
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
	echo "<br> L'équipe ".$id_equipe." détient ".$nbZonesEquipe." zones";
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
    "1" => getScoreEquipePartie($id_partie, 1),
    "2" => getScoreEquipePartie($id_partie, 2),
    "3" => getScoreEquipePartie($id_partie, 3),
    "4" => getScoreEquipePartie($id_partie, 4)
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
    "1" => getNombreFlashsEquipeZonePartie ($id_partie, 1, $id_zone),
    "2" => getNombreFlashsEquipeZonePartie ($id_partie, 2, $id_zone),
    "3" => getNombreFlashsEquipeZonePartie ($id_partie, 3, $id_zone),
    "4" => getNombreFlashsEquipeZonePartie ($id_partie, 4, $id_zone)
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
		$couleursTab[$zone]=getCouleurEquipe($meilleureEquipe);
	}
	print_r($couleursTab);
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
		$equipesZonesTab[$zone] = getMeilleureEquipeZone($id_partie, $zone);
	}
	print_r($equipesZonesTab);
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
	$couleurEquipe = $req->fetchColumn();
	return $couleurEquipe;
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
	$nb = $req->fetchColumn();
	return $nb;	
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
	$nb = $req->fetchColumn();
	return $nb;	
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
	$nb = $req->fetchColumn();
	return $nb;	
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
 * - id_partie : identifiant de la partie
 *
 * Output:
 * - array: tableau des joueurs avec leur score (clé : id_joueur, valeur : score)
 */
function getClassementJoueursPartie ($id_partie) {
	global $bdd;
	$array = array();
	for($i=1;$i<=getNbJoueursActifsPartie($id_partie);$i++)
		{
			$array[$i] = getNombreFlashsJoueurPartie($id_partie,$i);
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
	for($i=1;$i<=getNbJoueursActifsPartie($id_partie);$i++)
		{
			if($id_equipe == getEquipeJoueurPartieActive ($id_partie, $i))
				$array[$i] = getNombreFlashsJoueurPartie($id_partie,$i);
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
	for($i=1;$i<=getNbJoueursActifsPartie($id_partie);$i++)
		{
			$array[$i] = getNombreFlashsJoueurQRCodePartie ($id_partie, $i, $id_qrcode);
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