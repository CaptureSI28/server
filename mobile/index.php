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
			
				// Nombre de joueurs, score, derniers flashs de chaque équipe + classement
				if($_POST["infos_equipes"] == "true") {
					$nbJoueurs=getNombreJoueursActifsPartieEquipes($_POST["game_id"]);
					$response['nbJoueursEquipes'] = $nbJoueursEquipe;
					
					if(!$nbJoueurs) {
						$success = false;
					}
				
					$scoreEquipes=getScoreEquipesPartie($_POST["game_id"]);
					$response['scoreEquipes'] = $scoreEquipes;
					
					if(!$scoreEquipes) {
						$success = false;
					}

					$derniersFlashsEquipes=getDerniersFlashsEquipes($_POST["game_id"]);
					$response['derniersFlashsEquipes'] = $derniersFlashsEquipes;
					
					if(!$derniersFlashsEquipes) {
						$success = false;
					}

					$classementEquipesPartie=getClassementEquipesPartie($_POST["game_id"]);
					$response['classementEquipesPartie'] = $classementEquipesPartie;
					
					if(!$classementEquipesPartie) {
						$success = false;
					}
				}

				// Score et derniers flashs du joueur
				if($_POST["infos_joueur"] == "true") {
					$scoreJoueur=getScoreJoueur($_POST["game_id"],$_POST["player_id"]);
					$response['scoreJoueur'] = $scoreJoueur;

					if(!$scoreJoueur) {
						$success = false;
					}

					$derniersFlashsJoueur=getDerniersFlashsJoueur($_POST["game_id"],$_POST["player_id"]);
					$response['derniersFlashsJoueur'] = $derniersFlashsJoueur;

					if(!$derniersFlashsJoueur) {
						$success = false;
					}
				}
				
				
				// TODO Je ne sais pas si c'est plus pratique pour toi Joseph d'avoir la couleur ou l'identifiant de l'équipe ? Je mets les deux on supprimera la partie inutile après
				// Couleur de chaque zone			
				if($_POST["couleur_zone"] == "true") {
					$couleursZones=getCouleursZones($_POST["game_id"]);
					$response['couleursZones'] = $couleursZones;
					if(!$couleursZones) {
						$success = false;
					}
				}
				
				// Id équipe ayant capturé chaque zone
				if($_POST["equipe_zone"] == "true") {
					$equipesZones=getIdEquipeParZone($_POST["game_id"]);
					$response['equipesZones'] = $equipesZones;
					if(!$equipesZones) {
						$success = false;
					}
				}

				// Derniers flashs de la partie
				if($_POST["flashs_partie"] == "true") {
					$derniersFlashs=getDerniersFlashs($_POST["game_id"]);
					$response['derniersFlashs'] = $derniersFlashs;
					if(!$derniersFlashs) {
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
