<?php
	if (isset($_POST['session_id'])) {
		session_id($_POST['session_id']);
	}
	session_start();
	require_once('../fonctions.php');
	
	$response = array();
	
	if ($_POST['service'] === 'login' && !isset($_POST['session_id'])) {
		$response = validateCasTicket($_POST['cas_ticket'], $_POST['cas_service']);
	} else if (isset($_SESSION['login'])) {
		switch ($_POST['service']) {
			// Récupérer la liste des parties en cours
			case 'fetchGamesList':
				$response['success'] = 'YES';
				$response['games_list'] = getActiveGamesList();				
				break;
				
			// Créer une nouvelle partie
			case 'createNewGame':
				if (!isset($_POST['start_date'])) {
					$_POST['start_date'] = $_POST['debut'];
				}
				if (!isset($_POST['end_date'])) {
					$_POST['end_date'] = $_POST['fin'];
				}
				$newGame = newGame($_POST['name'], $_POST['start_date'], $_POST['end_date'], $_POST['password']);
				$response['success'] = $newGame ? 'YES' : 'NO';
				$response['new_game'] = $newGame;
				break;
				
			// Rejoindre une partie
			case 'joinGame':
				$success = joinGame(date('Y-m-d H:i:s', time()), $_POST['game_id'], $_POST['team_id'], getIdForPlayer($_SESSION['login']), $_POST['password']);
				$response['success'] = $success ? 'YES' : 'NO';
				break;
				
			// Flasher un QRCode
			case 'flash':
				$success = newFlash(date('Y-m-d H:i:s', time()), getIdForPlayer($_SESSION['login']), $_POST['qrcode']);
				$response['success'] = $success ? 'YES' : 'NO';
				break;
				
			case 'gameInfo':
				$playerid = getIdForPlayer($_SESSION['login']);
				$gameid = getPartieActiveJoueur($playerid);
				$nbJoueurs=getNombreJoueursActifsPartieEquipes($gameid);
				$scoreEquipes=getScoreEquipesPartie($gameid);
				$scoreJoueur=getScoreJoueurPartie($gameid,$playerid);
				$team_info = array();
				for ($i = 0; $i < 4; $i++) {
					$team_info[] = array(
						'score' => intval($scoreEquipes[$i]['score']),
						'nb_players' => intval($nbJoueurs[$i]['nbJoueurs'])
					);
				}
				$response['success'] = 'YES';
				$response['game_info'] = array(
					'player_team' => intval(getEquipeJoueurPartieActive($gameid, $playerid)),
					'player_score' => intval($scoreJoueur),
					'team_info' => $team_info
				);
				switch ($_POST['sub_service']) {
					case 'publicGameInfo':
						$teams = array();
						for ($i = 0; $i < 4; $i++) {
							$players = getListeJoueursActifsPartieEquipe($_POST['game_id'], $i + 1);
							$team = array();
							foreach ($players as $key => $row) {
								$team[] = $row['login'];
							}
							$teams[] = $team;
						}
						$response['teams'] = $teams;
						break;
					case 'playerProfile':
						$timePlayed = getTimePlayed($gameid,$playerid);
						$playerid = getIdForPlayer($_SESSION['login']);
						$gameid = getPartieActiveJoueur($playerid);
						$nbFlashsJoueurPartie = getNombreFlashsJoueurPartie ($gameid, $playerid);
						$response['player_name'] = $_SESSION['login'];
						$response['time_played'] = $timePlayed;
						$response['profile_info'] = array(
							$nbFlashsJoueurPartie . ' capture' . ($nbFlashsJoueurPartie > 1 ? 's' : ''),
							'15 missions accomplies',
							'2 missions en cours'
						);
						break;
					case 'teamProfile':
						$teamId = $_POST['team_id'];
						$playerid = getIdForPlayer($_SESSION['login']);
						$gameid = getPartieActiveJoueur($playerid);
						$nbJoueursEquipes = getNombreJoueursActifsPartieEquipe ($gameid, $teamId);
						$nbFlashsEquipePartie = getNombreFlashsEquipePartie ($gameid, $teamId);
						$response['team_info'] = array(
							$nbJoueursEquipes . ' joueur' . ($nbJoueursEquipes > 1 ? 's' : ''),
							$nbFlashsEquipePartie . ' capture' . ($nbFlashsEquipePartie > 1 ? 's' : ''),
							'45 missions accomplies',
							'15 missions en cours'
						);
						break;
					case 'map':
						$equipesZones=getIdEquipeParZone($gameid);
						$map = array();
						foreach ($equipesZones as $value) {
							$map[$value['zone']] = strval($value['equipe']);
						}
						$response['map'] = $map;
						break;
					case 'history':
						$derniersFlashs = getDerniersFlashs($gameid);
						$phrases = array();
						foreach ($derniersFlashs as $key => $row) {
							$phrases[] = $row['login'] . ' (équipe ' . $row['equipe'] . ') a flashé la zone ' . $row['qrcode'];
						}
						$response['history'] = $phrases;
						break;
					case 'rankings':
						$rankingsId = $_POST['rankings_id'];
						switch ($rankingsId) {
							default:
							case 0:
								$rankings = getOverallRankings($gameid);
								break;
							case 1:
								$rankings = getTeamRankings($gameid);
								break;
						}
						$response['rankings'] = $rankings;
						break;
					case 'settings':
						$response['nb_players_game'] = getNbJoueursActifsPartie ($id_partie);
						break;
					default:
						$response['success'] = 'NO';
						$response['failure'] = 'Unknown sub-service';
						break;
				}

			// Récupérer les infos sur la partie
			case 'infos_partie' :
			
				$success = true;
				$playerid = getIdForPlayer($_SESSION['login']);
				$gameid = getPartieActiveJoueur($playerid);
								
				// Sous services permettant de récupérer différentes infos
			
				// Nombre de joueurs et score de chaque équipe
				// "nbJoueursEquipes":[{"equipe":1,"nbJoueurs":0},...]
				if($_POST["infos_equipes"] == "true") {
					$listeJoueursEquipePartie = getListeJoueursActifsPartieEquipe($gameid, $teamId);
					$response['listeJoueursEquipePartie'] = $nbJoueurs;
					$nbJoueurs=getNombreJoueursActifsPartieEquipes($gameid);
					$response['nbJoueursEquipes'] = $nbJoueurs;
					
					if(!$nbJoueurs) {
						$success = false;
					}
				
					$scoreEquipes=getScoreEquipesPartie($gameid);
					$response['scoreEquipes'] = $scoreEquipes;
					
					if(!$scoreEquipes) {
						$success = false;
					}
				}
				
				// Score du joueur
				// "scoreEquipes":[{"equipe":1,"score":0}...]
				if($_POST["infos_joueur"] == "true") {
					$scoreJoueur=getScoreJoueurPartie($gameid,$playerid);
					$response['scoreJoueur'] = $scoreJoueur;
				}
				
				// TODO Je ne sais pas si c'est plus pratique pour toi Joseph d'avoir la couleur ou l'identifiant de l'équipe ? Je mets les deux on supprimera la partie inutile après
				
				// Couleur de chaque zone
				// "couleursZones":[{"zone":1,...]	
				if($_POST["couleur_zones"] == "true") {
					$couleursZones=getCouleursZones($gameid);
					$response['couleursZones'] = $couleursZones;
					if(!$couleursZones) {
						$success = false;
					}
				}
				
				// Id équipe ayant capturé chaque zone
				// "equipesZones":[{"zone":1,"equipe":"2"},...]
				if($_POST["equipe_zones"] == "true") {
					$equipesZones=getIdEquipeParZone($gameid);
					$response['equipesZones'] = $equipesZones;
					if(!$equipesZones) {
						$success = false;
					}
				}
				
				$response['success'] = 'YES';

				break;

			// Récupérer les classements
			case 'classements' :
					$success = true;
				switch($_POST['classement']){
					case 'flashs' :
						$classement=getClassementFlashs($_POST['game_id']);
						break;
					case 'points' :
						$classement=getClassementPoints($_POST['game_id']);
						break;
					default : $success = false;
				}

				$response['classement'] = $classement;
				$response['success'] = $success;
			break;		
			// Défault
			default:
				$response['failure'] = 'Unknown service';
				break;
		}
	} else {
		session_destroy();
		$response['failure'] = 'Unknown user';
	}
	
	echo json_encode($response);
?>
