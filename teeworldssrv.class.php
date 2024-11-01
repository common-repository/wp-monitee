<?php

////////////////////////////////////////
// Encodage du fichier : UTF-8
// Utilisation des tabulations : Oui
// 1 tabulation = 4 caractères
// Fins de lignes = LF (Unix)
////////////////////////////////////////

///////////////////////////////
// LICENCE
///////////////////////////////
//
// © Cédric Ducarre (SamRay1024), (23/03/2009)
//
// webmaster@jebulle.net
//
// Classe de lecture des informations d'un serveur Teeworlds.
//
// Ce logiciel est régi par la licence CeCILL soumise au droit français et
// respectant les principes de diffusion des logiciels libres. Vous pouvez
// utiliser, modifier et/ou redistribuer ce programme sous les conditions
// de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA
// sur le site "http://www.cecill.info".
//
// En contrepartie de l'accessibilité au code source et des droits de copie,
// de modification et de redistribution accordés par cette licence, il n'est
// offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
// seule une responsabilité restreinte pèse sur l'auteur du programme,  le
// titulaire des droits patrimoniaux et les concédants successifs.
//
// A cet égard  l'attention de l'utilisateur est attirée sur les risques
// associés au chargement,  à l'utilisation,  à la modification et/ou au
// développement et à la reproduction du logiciel par l'utilisateur étant
// donné sa spécificité de logiciel libre, qui peut le rendre complexe à
// manipuler et qui le réserve donc à des développeurs et des professionnels
// avertis possédant  des  connaissances  informatiques approfondies.  Les
// utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
// logiciel à leurs besoins dans des conditions permettant d'assurer la
// sécurité de leurs systèmes et ou de leurs données et, plus généralement,
// à l'utiliser et l'exploiter dans les mêmes conditions de sécurité.
//
// Le fait que vous puissiez accéder à cet en-tête signifie que vous avez
// pris connaissance de la licence CeCILL, et que vous en avez accepté les
// termes.
//
///////////////////////////////

/**
 * Classe de surveillance d'un serveur Teeworlds.
 *
 * Permet de connaître l'état d'un serveur en temps réel : nom, carte en cours, joueurs connectés ...
 * 
 * Exemple d'utilisation :
 * 
 *		$oTwSrv = new TeeworldsSrv( '92.243.2.203', 8303 );
 *		$sError = $oTwSrv->readSrvInfos();
 *	
 *		if( empty($sError) ) {
 *		
 *			echo '<pre>';
 *			echo 'Nom : '.$oTwSrv->getName(),"\n";
 *			echo 'Version : '.$oTwSrv->getVersion(),"\n";
 *			echo 'Type : '.$oTwSrv->getGameType(),"\n";
 *			echo 'Map : '.$oTwSrv->getMap(),"\n";
 *			echo 'Flags : '.$oTwSrv->getFlags(),"\n";
 *			echo 'Progression : '.$oTwSrv->getProgression(),"\n";
 *			echo 'Joueurs : '.$oTwSrv->getNumPlayers(),'/',$oTwSrv->getMaxPlayers(),"\n";
 *			echo 'Liste des joueurs :'."\n";
 *			print_r($oTwSrv->getPlayers());
 *			echo '</pre>';
 *		}
 *		else echo '<p>'.$sError.'</p>';
 *
 * @author SamRay1024
 * @copyright Bubulles Créations, http://jebulle.net
 * @link http://doc.jebulle.net/classes/teeworldsrv
 * @since 17/03/2009
 * @version 23/03/2009
 */
class TeeworldsSrv {

	/**
	 * Adresse du serveur (DNS ou IP)
	 *
	 * @var string
	 */
	private $sSrvHost = '';
	
	/**
	 * Port du serveur
	 *
	 * @var integer
	 */
	private $iSrvPort = 8303;
	
	/**
	 * Version de Teeworlds exécutée
	 *
	 * @var string
	 */
	private $sInfoVersion = '';
	
	/**
	 * Nom du serveur
	 *
	 * @var string
	 */
	private $sInfoName = '';
	
	/**
	 * Carte en cours
	 *
	 * @var string
	 */
	private $sInfoMap = '';
	
	/**
	 * Type de partie (DM, TDM, CTF)
	 *
	 * @var string
	 */
	private $sInfoGameType = '';
	
	/**
	 * Drapeaux divers
	 *
	 * @var integer
	 */
	private $iInfosFlags = 0;
	
	/**
	 * Progression
	 *
	 * @var integer
	 */
	private $iInfoProgression = 0;
	
	/**
	 * Nombre de joueurs connectés
	 *
	 * @var integer
	 */
	private $iInfoNumPlayers = 0;
	
	/**
	 * Nombre de joueurs maximal
	 *
	 * @var integer
	 */
	private $iInfoMaxPlayers = 0;
	
	/**
	 * Tableau des joueurs connectés
	 *
	 * @var array
	 */
	private $aPlayers = array();

	/**
	 * Numéro de l'erreur si l'ouverture de la socket echoue
	 *
	 * @var	integer
	 */
	private $iSockErrNo = 0;
	
	/**
	 * Message d'erreur si l'ouverture de la socket echoue
	 *
	 * @var	string
	 */
	private $sSockErrStr = '';
	
	/**
	 * Constructeur
	 *
	 * @param	string	$sServerAddress	Adresse du serveur (DNS ou IP)
	 * @param	integer	$iServerPort	Port du serveur (8303 par défaut)
	 */
	public function __construct( $sServerAddress, $iServerPort = 8303 ) {
	
		$this->sSrvHost	= trim((string) $sServerAddress);
		$this->iSrvPort	= (int) $iServerPort;
	}
	
	/**
	 * Lire l'état du serveur.
	 *
	 */
	public function readSrvInfos() {
		
		// Connexion au serveur
		$hSocket = @fsockopen(
			'udp://'.gethostbyname($this->sSrvHost),
			$this->iSrvPort,
			$this->iSockErrNo,
			$this->sSockErrStr,
			5
		);
				
		// Si l'ouverture est ok
		if( $hSocket !== false ) {
			
			$sRetour	= '';
			$aGameTypes	= array('DM', 'TDM', 'CTF');
			
			stream_set_timeout($hSocket, 5);
		
			// Envoi de la demande d'information
			fwrite($hSocket, "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffgief");
		
			// Lecture de la réponse (tronquée des 14 caratères d'entêtes inutiles)
			$sResponse = substr(fread($hSocket, 1024), 14);
			
			// Si réponse reçue
			if( strlen($sResponse) > 5 ) {
				
				// Passage en tableau
				$aResponse = explode("\x00", $sResponse);
				
				// Lecture des infos
				$this->sInfoVersion		= $aResponse[0];
				$this->sInfoName		= $aResponse[1];
				$this->sInfoMap			= $aResponse[2];
				$this->sInfoGameType	= (in_array($aResponse[3], $aGameTypes) ? $aResponse[3] : '?');
				$this->iInfosFlags		= (int) $aResponse[4];
				$this->iInfoProgression	= (int) $aResponse[5];
				$this->iInfoNumPlayers	= (int) $aResponse[6];
				$this->iInfoMaxPlayers	= (int) $aResponse[7];
				
				// Récupération des joueurs
				$this->aPlayers			= array();
				
				for($i = 0 ; $i < $this->iInfoNumPlayers ; $i++) {
					
					$this->aPlayers[] = array(
						'name'	=> $aResponse[8 + ($i * 2) + 1],
						'score'	=> $aResponse[8 + ($i * 2)]
					);
				}
			}
			else $sRetour = 'Aucun serveur détecté.';
			
			// Fermeture socket
			fclose($hSocket);
			
			return $sRetour;
		}
		else return 'Erreur de connexion.';
	}
	
	/**
	 * Lire le numéro de version du serveur
	 *
	 * @return string
	 */
	public function getVersion()		{ return $this->sInfoVersion; }
	
	/**
	 * Lire le nom du serveur
	 *
	 * @return string
	 */
	public function getName()			{ return $this->sInfoName; }
	
	/**
	 * Lire le nom de la carte en cours
	 *
	 * @return string
	 */
	public function getMap()			{ return $this->sInfoMap; }
	
	/**
	 * Lire le type de partie
	 *
	 * @return string
	 */
	public function getGameType()		{ return $this->sInfoGameType; }
	
	/**
	 * Lire les drapeaux
	 *
	 * @return integer
	 */
	public function getFlags()			{ return $this->iInfosFlags; }
	
	/**
	 * Lire la progression
	 *
	 * @return integer
	 */
	public function getProgression()	{ return $this->iInfoProgression; }
	
	/**
	 * Lire le nombre de joueurs connectés
	 *
	 * @return integer
	 */
	public function getNumPlayers()		{ return $this->iInfoNumPlayers; }
	
	/**
	 * Lire le nombre de joueurs autorisés
	 *
	 * @return integer
	 */
	public function getMaxPlayers() 	{ return $this->iInfoMaxPlayers; }
	
	/**
	 * Lire le tableau des utilisateurs
	 * 
	 * Exemple : récupérer les infos du 4e utilisateur
	 * 
	 * 		$aPlayers = $oSrv->getPlayers();
	 * 		echo $aPlayers[3]['name'];
	 * 		echo $aPlayers[3]['score'];
	 *
	 * @return array
	 */
	public function getPlayers()		{ return $this->aPlayers; }
}
?>