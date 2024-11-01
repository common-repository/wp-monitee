<?php
/**
 * @package WP-MoniTee
 * @author SamRay1024
 * @version 1.0.1
 */
/*
Plugin Name: WP-MoniTee
Plugin URI: http://wordpress.org/extend/plugins/wp-monitee/
Description: Fournit un widget pour surveiller un ou plusieurs serveurs Teeworlds. Vous pouvez l'ajouter via le paramétrage de votre thème ou placer l'appel manuellement dans le code de votre thème : <code>&lt;?php wp_monitee(); ?&gt;</code>.
Version: 1.0.1
Author: SamRay1024
Author URI: http://jebulle.net/
*/

///////////////////////////////
// LICENSE
///////////////////////////////
//
// © Cédric Ducarre (SamRay1024), (23/03/2009)
//
// webmaster@jebulle.net
//
// This plugin provides a widget to show the status of your Teeworlds server(s).
//
// This software is governed by the CeCILL license under French law and
// abiding by the rules of distribution of free software.  You can  use,
// modify and/ or redistribute the software under the terms of the CeCILL
// license as circulated by CEA, CNRS and INRIA at the following URL
// "http://www.cecill.info".
//
// As a counterpart to the access to the source code and  rights to copy,
// modify and redistribute granted by the license, users are provided only
// with a limited warranty  and the software's author,  the holder of the
// economic rights,  and the successive licensors  have only  limited
// liability.
//
// In this respect, the user's attention is drawn to the risks associated
// with loading,  using,  modifying and/or developing or reproducing the
// software by the user in light of its specific status of free software,
// that may mean  that it is complicated to manipulate,  and  that  also
// therefore means  that it is reserved for developers  and  experienced
// professionals having in-depth computer knowledge. Users are therefore
// encouraged to load and test the software's suitability as regards their
// requirements in conditions enabling the security of their systems and/or
// data to be ensured and,  more generally, to use and operate it in the
// same conditions as regards security.
//
// The fact that you are presently reading this means that you have hadz
// knowledge of the CeCILL license and that you accept its terms.
//
///////////////////////////////

require_once(ABSPATH . 'wp-content/plugins/wp-monitee/teeworldssrv.class.php');

class WpMoniTee {

	/**
	 * Constructeur
	 *
	 */
	public function __construct() {

		// Enregistrement méthode à exécuter lors de l'activation du plugin
		register_activation_hook(__FILE__, array($this, 'onActivate'));

		// Déclaration méthode d'initialisation du widget
		add_action('widgets_init', array($this, 'onInitWidget'));

		// Déclaration méthode de création du menu de configuration
		add_action('admin_menu', array($this, 'onAdminMenu'));
	}

	/**
	 * Activation du plugin
	 *
	 */
	public function onActivate() {

		// Ajout de l'option où seront enregistrées les données du plugin
		add_option('wpmonitee_options', 'Serveur(s) Teeworlds;15', '', 'no');
	}

	/**
	 * Initialisation du widget
	 *
	 */
	public function onInitWidget() {

		// Si les fonctions d'enregistrement n'existent pas on sort
		if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

		// Enregistrement du widget
		register_sidebar_widget('WP-MoniTee', array($this, 'onWidgetEcho'));

		// Enregistrement des options
		register_widget_control('WP-MoniTee', array($this, 'onWidgetOptions'));
	}

	/**
	 * Création du menu de configuration
	 */
	public function onAdminMenu() {

		add_options_page('WP-MoniTee - Configuration', 'WP-MoniTee', 8, __FILE__, array($this, 'onPluginOptions'));
	}

	/**
	 * Affichage du widget
	 *
	 */
	public function onWidgetEcho( $args = null ) {

		// Permet de récupérer $before_widget, $after_widget, $before_title, $after_title
		extract($args);

		// Passe à vrai s'il faut enregistrer les nouvelles informations du serveur
		$bSaveData = false;

		// Récupération des options du widget
		$aOptions = explode(';', get_option('wpmonitee_options'));

		// Affichage du titre
		echo $before_widget;
		echo $before_title . $aOptions[0] . $after_title;

		// Nombre de serveurs
		$iNbSrv = sizeof($aOptions) - 2;

		// Pour chaque serveur
		for($i = 0 ; $i < $iNbSrv ; $i++) {

			// Lecture infos
			$aServerData	= explode(':', $aOptions[$i + 2]);

			// Si les infos serveur sont périmées ou que l'on ne les a pas => nouveau scan
			if( (time() - $aServerData[2]) > $aOptions[1] || !isset($aServerData[3]) ) {

				$sServerInfos	= $this->getSrvInfos($aServerData[0], $aServerData[1]);
				$bSaveData		= true;

				// On réecrit les données du serveur courant
				$aOptions[$i + 2] = $aServerData[0] .':'. $aServerData[1] .':'. time() .':'. $sServerInfos;
			}
			// Sinon on se contente de lire les infos enregistrées
			else $sServerInfos = $aServerData[3];

			echo '<p><strong>'.$aServerData[0].':'.$aServerData[1].'</p>';

			if( strpos($sServerInfos, '`') !== false ) {

				$aServerInfos = explode('`', $sServerInfos);
				echo '<ul>';
				echo '<li>'.$aServerInfos[0].'</li>';
				echo '<li><span>Type :</span> '.$aServerInfos[1].'</li>';
				echo '<li><span>Map :</span> '.$aServerInfos[2].'</li>';
				echo '<li><span>Joueurs :</span> '.$aServerInfos[3].'/'.$aServerInfos[4].'</li>';
				echo '</ul>';
			}
			else echo '<p>'.$sServerInfos.'</p>';
		}

		if($iNbSrv <= 0) echo 'Aucun serveur à surveiller.';

		// Affichage pied de widget
		echo $after_widget;

		// Maj options si nécessaire
		if( $bSaveData ) update_option('wpmonitee_options', implode(';', $aOptions));
	}

	/**
	 * Affichage des options du widget
	 *
	 * Les options sont enregistrées dans l'enregistrement 'wpmonitee_options' de
	 * la table wp_options.
	 *
	 * Les données du widget sont stockées dans une unique chaîne. Chaque élément est séparé
	 * d'un autre par un ';' dans l'ordre :
	 *
	 * - titre du widget
	 * - temps d'attente minimum entre deux requêtes (pour ne pas le polluer si le site génère du traffic)
	 * - infos serveur 1
	 * - infos serveur 2
	 * - ...
	 * - infos serveur n
	 *
	 * Les informations serveur sont elles-même redécoupées grâce au séparateur ':'. Dans l'ordre :
	 *
	 * - adresse du serveur (IP ou DNS)
	 * - port du serveur
	 * - timestamp de la dernière requête envoyée au serveur
	 * - informations lues (si la connexion a réussie)
	 *
	 * Les informations du serveur sont à nouveau découpées, cette fois-ci avec '`' :
	 *
	 * - nom du serveur
	 * - type de partie
	 * - carte en cours
	 * - nombre de joueurs connectés
	 * - nombre de joueurs maximum
	 *
	 * Exemples :
	 *
	 * 	- 2 serveurs ajoutés mais pas encore scannés :
	 *
	 * 		Mes serveurs Teeworlds;15;myserver.com:8303:0;45.28.159.2:8303:0
	 *
	 *  - 2 serveurs ajoutés, un en ligne, l'autre en erreur :
	 *
	 * 		Mes serveurs Teeworlds;15;myserver.com:8303:1237483173:Battle 4vs4`CTF`ctf3`8`10;45.28.159.2:8303:1237483174;Erreur de connexion.
	 *
	 */
	public function onWidgetOptions() {

		// Enregistrement des modifications
		$this->saveOptions();

		// Lecture options et extraction des serveurs
		$aOptions	= explode(';', get_option('wpmonitee_options'));
		$sServers	= $this->extractServerFromOptions($aOptions);

		// Construction du formulaire
		echo
			'<p>
				<label for="wpmonitee_options_title">Titre :</label>
				<input id="wpmonitee_options_title" name="wpmonitee_options_title" type="text" value="'.$aOptions[0].'" />
			</p>
			<p>
				<label for="wpmonitee_option_servers">Liste des serveurs :</label>
				<textarea id="wpmonitee_options_servers" name="wpmonitee_options_servers" rows="4">'.$sServers.'</textarea>
				<br /><em>Un par ligne. Ex : 214.156.5.155:8303 ou my.dns.com:8303.</em>
			</p>
			<p>
				<label for="wpmonitee_options_time">Vérifier toutes les :</label>
				<input id="wpmonitee_options_time" name="wpmonitee_options_time" type="text" maxlenght="3" value="'.$aOptions[1].'" class="small-text" />secondes
			</p>';
	}

	/**
	 * Méthode de callback appelée lors de l'ouverture de la page des options depuis l'administration de Wordpress
	 */
	public function onPluginOptions() {

		// Enregistrement des modifications
		$this->saveOptions();

		// Récupération des options du widget
		$aOptions = explode(';', get_option('wpmonitee_options'));

		// Extraction des adresses
		$sSrvList = $this->extractServerFromOptions($aOptions);

		include(dirname(__FILE__).'/options.php');
	}

	/**
	 * Récupérer les informations du serveur
	 *
	 * La méthode commence par analyser les informations telles qu'enregistrées dans la base.
	 * Si les infos sont périmées, une requête est envoyée au serveur Teeworlds pour lire
	 * les nouvelles informations.
	 *
	 * La chaîne retournée est de la forme :
	 *
	 * 		nom du serveur`type de partie`carte en cours`nombres de joueurs connectés`nombre de joueurs maximum
	 *
	 * Pour l'exploiter, il suffit de faire : $aSrvInfos = explode('`', $sSrvInfos);
	 *
	 * Si les informations n'ont pu être lues, la méthode renvoie une chaîne avec le message d'erreur.
	 * Cette chaîne ne contient donc pas le séparateur "`" (Alt Gr + 7).
	 *
	 * @param string	$sSrvAddress	Adresse du serveur (IP ou DNS).
	 * @param integer	$iSrvPort		Port du serveur.
	 * @param integer	$iLastRequest	Timestamp de la dernière requête envoyée.
	 * @param integer	$iMaxTime		Temps en secondes au delà duquel les informations sont considérées comme périmées.
	 * @return string					Informations du serveur.
	 */
	private function getSrvInfos( $sSrvAddress, $iSrvPort ) {

		$oTwSrv		= new TeeworldsSrv($sSrvAddress, $iSrvPort);
		$sErreur	= $oTwSrv->readSrvInfos();

		// Si lecture réussie
		if( empty($sErreur) )
		return	$oTwSrv->getName()			.'`'
		.$oTwSrv->getGameType()		.'`'
		.$oTwSrv->getMap()			.'`'
		.$oTwSrv->getNumPlayers()	.'`'
		.$oTwSrv->getMaxPlayers();

		else return $sErreur;
	}

	/**
	 * Enregistrer les options saisies dans le formulaire de configuration
	 *
	 * L'opération n'est réalisée que si l'un des deux formulaires (widget
	 * ou page de configuration) est validé.
	 * 
	 */
	private function saveOptions() {

		if( (isset($_POST['save-widget']) || isset($_POST['save-wpmonitee-options'])) &&
			(isset($_POST['wpmonitee_options_title']) &&
			 isset($_POST['wpmonitee_options_servers']) &&
			 isset($_POST['wpmonitee_options_time'])) ) {

			// Récupération données formulaire
			$sTitle		= (string)	$_POST['wpmonitee_options_title'];
			$aServers	= explode('<br />', nl2br(trim($_POST['wpmonitee_options_servers'])));
			$iTime		= (int)		$_POST['wpmonitee_options_time'];

			// Création tableau des options
			$aOptions = array($sTitle, $iTime);
			foreach( $aServers as $sValue ) {
				$aOptions[] = $sValue .':0';
			}

			// Maj options
			update_option('wpmonitee_options', implode(';', $aOptions));
		}
	}

	/**
	 * Extraire la liste des serveurs depuis les options enregistrées
	 *
	 * @param array	$aOptions	Tableau des options du plugin.
	 * @return string			Serveurs, séparés par un retour chariot (\n).
	 */
	private function extractServerFromOptions($aOptions) {

		$iNbSrv		= sizeof($aOptions) - 2;
		$sServers	= '';

		// Extraction des serveurs
		for($i = 0 ; $i < $iNbSrv ; $i++) {

			$aServer = explode(':', $aOptions[$i + 2]);
			$sServers .= $aServer[0] . ':' . $aServer[1];
		}

		return $sServers;
	}
}

/**
 * Marqueur de template pour afficher la liste des serveurs dans une page.
 *
 * @global WpMoniTee $oWpMoniTee Instance courante du plugin.
 */
function wp_monitee() {

	global $oWpMoniTee;
	$oWpMoniTee->onWidgetEcho();
}

// Instanciation
$oWpMoniTee = new WpMoniTee();

?>