<?php

/**
 * Class Hal_Settings_Submissions
 * Affichage de la page Mes dépôts
 * Parametres des actions en fonction des dépôts (fulltext, notice, ...)
 */
class Hal_Settings_Submissions
{
	/**
	 * Blocs dispo
	 */
	const TYPE_MODIFY_FILE	=	'modify_file';	//Fichier en demande de modification
	const TYPE_OFFLINE_FILE	=	'offline_file';	//Fichier en attente de validation
	const TYPE_ONLINE_FILE	=	'online_file';	//Fichier en ligne
	const TYPE_MODIFY_REF	=	'modify_ref';	//Notice en demande de modification
	const TYPE_OFFLINE_REF	=	'offline_ref';	//Notice en attente de validation
	const TYPE_ONLINE_REF	=	'online_ref';	//Notice en ligne
	const TYPE_MYSPACE		=	'myspace';		//Document dans l'espace du déposant
	const TYPE_EMBARGO		=	'embargo';		//Document sous embargo

	/**
	 * Actions
	 */
	const ACTION_SEE		=	'see';
	const ACTION_MODIFY		=	'update';
    const ACTION_MODERATE   =   'moderate';
	const ACTION_REPLY		=	'reply';
	const ACTION_DELETE		=	'delete';
	const ACTION_METADATA	=	'modify';
	const ACTION_VERSION	=	'replace';
	const ACTION_TRANSFERT	=	'transfert';
    const ACTION_UNSHARE	=	'unshare';
	const ACTION_ADDFILE	=	'addfile';
	const ACTION_FILE		=	'file';
	const ACTION_SUBMIT		=	'submit';
	const ACTION_ONLINE		=	'online';
	const ACTION_TAMPONNER  =	'tamponner';
	const ACTION_DETAMPONNER=	'detamponner';
	const ACTION_LIBRARY	=	'library';
	const ACTION_REFRESH	=	'refresh';
	const ACTION_HISTORY	=	'history';
	const ACTION_RELATED	=	'related';
	const ACTION_COPY	    =	'copy';
	const ACTION_CLAIM		=	'claim';

	/**
	 * Actions disponibles en fonction du type de documents
	 * @var array
	 */
	static protected $_settings = array(
		self::TYPE_MODIFY_FILE => array(
				self::ACTION_SEE, 
				self::ACTION_MODIFY, 
				self::ACTION_REPLY, 
				self::ACTION_DELETE),
		self::TYPE_OFFLINE_FILE => array(
				self::ACTION_SEE,
                                self::ACTION_DELETE),
		self::TYPE_ONLINE_FILE => array(
				self::ACTION_SEE,
				self::ACTION_METADATA,
                                self::ACTION_ADDFILE,
                                self::ACTION_VERSION,
                                self::ACTION_RELATED,
                                self::ACTION_COPY,
				self::ACTION_TRANSFERT,
                self::ACTION_UNSHARE),
		self::TYPE_MODIFY_REF => array(
				self::ACTION_SEE, 
				self::ACTION_MODIFY, 
				self::ACTION_REPLY, 
				self::ACTION_DELETE),	
		self::TYPE_OFFLINE_REF => array(
				self::ACTION_SEE,
                                self::ACTION_DELETE),
		self::TYPE_ONLINE_REF => array(
				self::ACTION_SEE, 
				self::ACTION_FILE,
				self::ACTION_METADATA,
                                self::ACTION_RELATED,
                                self::ACTION_COPY,
				self::ACTION_DELETE,
				self::ACTION_TRANSFERT,
                self::ACTION_UNSHARE),
		self::TYPE_MYSPACE => array(
				self::ACTION_SEE, 
				self::ACTION_MODIFY, 
				self::ACTION_SUBMIT, 
                                self::ACTION_CLAIM,
				self::ACTION_TRANSFERT),	
		self::TYPE_EMBARGO => array(
				self::ACTION_SEE, 
				self::ACTION_METADATA, 
				self::ACTION_ONLINE, 
                                self::ACTION_CLAIM,
				self::ACTION_TRANSFERT)
	);

    static protected $_iconActions = array(
        self::ACTION_SEE		=>	'glyphicon glyphicon-eye-open',
        self::ACTION_MODIFY	    =>  'glyphicon glyphicon-pencil',
        self::ACTION_METADATA	=>	'glyphicon glyphicon-pencil',
        self::ACTION_MODERATE	=>  'glyphicon glyphicon-retweet',
        self::ACTION_REPLY		=>	'glyphicon glyphicon-comment',
        self::ACTION_DELETE		=>	'glyphicon glyphicon-trash',
        self::ACTION_VERSION	=>	'glyphicon glyphicon-plus',
        self::ACTION_TRANSFERT	=>	'glyphicon glyphicon-user',
        self::ACTION_UNSHARE	=>	'glyphicon glyphicon-remove',
        self::ACTION_ADDFILE	=>	'glyphicon glyphicon-file',
        self::ACTION_FILE		=>	'glyphicon glyphicon-file',
        self::ACTION_LIBRARY	=>	'glyphicon glyphicon-book',
        self::ACTION_TAMPONNER  =>	'glyphicon glyphicon-tag',
        self::ACTION_DETAMPONNER=>	'glyphicon glyphicon-remove',
        self::ACTION_REFRESH    =>	'glyphicon glyphicon-refresh',
        self::ACTION_HISTORY    =>	'glyphicon glyphicon-calendar',
        self::ACTION_RELATED    =>	'glyphicon glyphicon-link',
        self::ACTION_COPY       =>	'glyphicon glyphicon-export',
        self::ACTION_CLAIM	=>	'glyphicon glyphicon-send',
    );
	
	/**
	 * Blocs à afficher tout le temps
	 * @var array
	 */
	static protected $_display = array(
		self::TYPE_ONLINE_FILE, 
		self::TYPE_ONLINE_REF
	);

	/**
	 * Retourne la configuration de la page "Mes dépôts"
	 * @return array
	 */
	static public function getSettings()
	{
		return self::$_settings;
	}
	
	/**
	 * indique si on affiche un bloc
	 * @param string $type
	 * @return boolean
	 */
	static public function displayIfEmpty($type)
	{
		return in_array($type, self::$_display);
	}

    static public function getIcon($action)
    {
        return self::$_iconActions[$action];
    }
	
}