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
				
			// Récupérer les infos sur la partie
			case 'infos_partie' :
			
				$success = true;
				$playerid = getIdForPlayer($_SESSION['login']);
				$gameid = getPartieActiveJoueur($playerid);
								
				// Sous services permettant de récupérer différentes infos
			
				// Nombre de joueurs et score de chaque équipe
				// "nbJoueursEquipes":[{"equipe":1,"nbJoueurs":0},...]
				if($_POST["infos_equipes"] == "true") {
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
				
				if ($success) {
						$response['success'] = 'YES';
				}
				else {
					 	$response['success'] = 'NO';
				}
					 	
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
