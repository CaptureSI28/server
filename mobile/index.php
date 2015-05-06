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
				$newGame = newGame($_POST['name'], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time() + 7 * 24 * 60 * 60), $_POST['password']);
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
				
				// Sous services permettant de récupérer différentes infos
			
				// Nombre de joueurs et score de chaque équipe
				if($_POST["nb_joueurs_equipe"] == "true") {
					$nbJoueurs1=getNbJoueursEquipe($_POST["game_id"],"1");
					$response['nbJoueursEquipe1'] = $nbJoueurs1;
					
					$nbJoueurs2=getNbJoueursEquipe($_POST["game_id"],"2");
					$response['nbJoueursEquipe2'] = $nbJoueurs2;
					
					$nbJoueurs3=getNbJoueursEquipe($_POST["game_id"],"3");
					$response['nbJoueursEquipe3'] = $nbJoueurs3;
					
					$nbJoueurs4=getNbJoueursEquipe($_POST["game_id"],"4");
					$response['nbJoueursEquipe4'] = $nbJoueurs4;
					
					if(!$nbJoueurs1 || !$nbJoueurs2 || !$nbJoueurs3 || !$nbJoueurs4) {
						$success = false;
					}
				}
				
				// Score de chaque équipe
				if($_POST["score_equipes"] == "true") {
					$scoreEquipe1=getScoreEquipe($_POST["game_id"],"1");
					$response['scoreEquipe1'] = $scoreEquipe1;
					
					$scoreEquipe2=getScoreEquipe($_POST["game_id"],"2");
					$response['scoreEquipe2'] = $scoreEquipe2;
					
					$scoreEquipe3=getScoreEquipe($_POST["game_id"],"3");
					$response['scoreEquipe3'] = $scoreEquipe3;
					
					$scoreEquipe4=getScoreEquipe($_POST["game_id"],"4");
					$response['scoreEquipe4'] = $scoreEquipe4;
					
					if(!$scoreEquipe1 || !$scoreEquipe2 || !$scoreEquipe3 || !$scoreEquipe4) {
						$success = false;
					}
				}
				
				// Score du joueur
				if($_POST["score_joueur"] == "true") {
					$scoreJoueur=getScoreJoueur($_POST["game_id"],$_POST["player_id"]);
					$response['scoreJoueur'] = $scoreJoueur;
					if(!$scoreJoueur) {
						$success = false;
					}
				}
				
				// Couleur de la zone			
				else if($_POST["couleur_zones"] == "true") {
					// TODO Récuperer couleur de chaque zone, pas que d'une seule, à modifier :
					$couleurZone=getCouleurZone($_POST["game_id"], $_POST["zone"]);
					$response['couleurZone'] = $couleurZone;
					if(!$couleurZone) {
						$success = false;
					}
				}
				
				if ($success)
						$response['success'] = 'YES';
				else
					 	$response['success'] = 'NO';
					 	
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
