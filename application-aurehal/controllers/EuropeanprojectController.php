<?php

class EuropeanprojectController extends Aurehal_Controller_Referentiel
{
	public function init ()
	{
		$this->_title = array(
			'browse'    => 'Consultation des projets européens',
			'modify_1'  => 'Modification d\'un projet Européen',
			'modify_2'  => 'Projet Européen modifié',
			'create_1'  => 'Création d\'un projet Européen',
			'create_2'  => 'Impossible de créer le projet Européen',
			'create_3'  => 'Projet Européen créé',
			'replace_1' => 'Remplacement de projets Européen',
			'replace_2' => 'Résumé des modifications des projets Européen',
			'read'     => 'Fiche d\'un projet européen'
		);
	
		$this->_info = array (
			'info_1' => 'Les modifications du projet Européen ? ont été prises en compte',
			'info_2' => 'La réindexation des données peut prendre quelques minutes pour être effective lors de la consultation de nos portails.',
			'info_3' => 'Vous essayer de creer un doublon du projet Européen ?',
			'info_4' => 'Le projet Européen ? a été créé',
			'info_5' => 'La réindexation peut prendre quelques minutes, le projet peut apparaitre après quleques minutes sur nos portails.',
		    'info_6' => 'Une erreur s\'est produite lors de l\'enregistrement du projet européen, votre saisie n\'a pas été prise en compte.'   
		);
	
		$this->_description = array (
			'browse'  => 'Ce module vous permet de consulter la liste des projets européens.',
			'create'  => 'Ce module vous permet de créer de nouveaux projets européens.',
			'replace' => 'Ce module vous permet de remplacer des projets européens non valide par un projet européen valide.',
			'modify'  => 'Ce module vous permet de modifier un projet européen existant.'
		);
		
		$this->_name          = 'EuropeanProject';
		$this->_class         = 'Ccsd_Referentiels_Europeanproject';	
		$this->_head_columns = array ('id', 'titre', 'acronyme', 'numero', 'fundedby', 'ACTIONS');
		$this->_columns_solR = array("id"=>"docid", "titre"=>"title_s", "acronyme" => "acronym_s", "numero"=>"reference_s", "fundedby" => "financing_s", "valid" => "valid_s");
		$this->_columns_dB   = array("id"=>"PROJEUROPID", "titre"=>"TITRE", "acronyme" => "ACRONYME", "numero" => "NUMERO", "fundedby" => "FUNDEDBY" , "valid" => "VALID");
	}
}