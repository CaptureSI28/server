<?php
	define('DB_HOST', 'localhost');
	define('DB_NAME', 'si28');
	define('DB_USER', 'root');
	define('DB_PASSWORD', 'root');
	define('DB_STRUCTURE_FILE', 'mysql_db_structure.sql');
?>
	<style type="text/css">
		ul > li {
			color: #00CC66;
		}
		ul > li:last-of-type:not(:last-child) {
			color: #0077DD;
		}
		ul > ul:last-of-type > li {
			color: #FF0055;
		}
	</style>
	<h1>Réinitialisation de la base de données</h1>
	<ul>
		<li>Connexion au serveur MySQL</li>
<?php
	try {
		$connexion_mysql = new PDO('mysql:host=' . DB_HOST . ';charset=utf8', DB_USER, DB_PASSWORD);
		$connexion_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$connexion_mysql->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	} catch (PDOException $e) {
		die('<ul><li>Connexion impossible:<pre>' . $e->getMessage() . '</pre></li></ul>');
	}
?>
		<li>Création de la base de données <?php echo DB_NAME; ?></li>
<?php
	try {
		$reponse = $connexion_mysql->query('
			DROP DATABASE IF EXISTS ' . DB_NAME . ';
		');
		$reponse = $connexion_mysql->query('
			CREATE DATABASE ' . DB_NAME . ' DEFAULT COLLATE utf8_general_ci;
		');
	} catch (PDOException $e) {
		die('<ul><li>Création impossible:<pre>' . $e->getMessage() . '</pre></li></ul>');
	}
?>
		<li>Connexion à la base de données <?php echo DB_NAME; ?></li>
<?php
	try {
		$bdd = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASSWORD);
		$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$bdd->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	} catch (PDOException $e) {
		die('<ul><li>Connexion impossible:<pre>' . $e->getMessage() . '</pre></li></ul>');
	}
?>
		<li>Importation de la structure de la base de données <?php echo DB_NAME; ?></li>
<?php
	try {
		$query = file_get_contents(DB_STRUCTURE_FILE);
		if ($query === false) {
			die('<ul><li>Impossible d\'ouvrir le fichier "' . DB_STRUCTURE_FILE . '"</li></ul>');
		}
		$subqueries = explode(';', $query);
		$subqueries = array_filter($subqueries, function($q){return !preg_match('/^\s*$/', $q);});
		foreach ($subqueries as $subquery) {
?>
		<li>Exécution de <pre><?php echo $subquery; ?></pre></li>
<?php
			$reponse = $bdd->query($subquery);
		}
	} catch (Exception $e) {
		die('<ul><li>Importation impossible:<pre>' . $e->getMessage() . '</pre></li></ul>');
	}
?>
	</ul>
