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

/*
 * Input:
 * - equipeID: identifiant d'une equipe
 *
 * Output:
 * - nbFlashs: nb flashs de l'equipe demandee
 */
function getNbFlashsEquipe ($equipeID) {
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