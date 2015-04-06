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
			case 'fetchGamesList':
				$response['success'] = 'YES';
				$response['games_list'] = getActiveGamesList();
				break;
			case 'createNewGame':
				$newGame = newGame($_POST['name'], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time() + 7 * 24 * 60 * 60), $_POST['password']);
				$response['success'] = $newGame ? 'YES' : 'NO';
				$response['new_game'] = $newGame;
				break;
			case 'joinGame':
				$success = joinGame(date('Y-m-d H:i:s', time()), $_POST['game_id'], $_POST['team_id'], getIdForPlayer($_SESSION['login']), $_POST['password']);
				$response['success'] = $success ? 'YES' : 'NO';
				break;
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
