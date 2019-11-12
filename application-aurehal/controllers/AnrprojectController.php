<?php

class AnrprojectController extends Aurehal_Controller_Referentiel
{
	public function init ()
	{	
		$this->_title = array(
			'browse'    => 'Consultation des projets ANR',
			'modify_1'  => 'Modification d\'un projet ANR',
			'modify_2'  => 'Projet ANR modifié',
			'create_1'  => 'Création d\'un projet ANR',
			'create_2'  => 'Impossible de créer le projet ANR',
			'create_3'  => 'Projet ANR créé',
			'replace_1' => 'Remplacement de projets ANR',
			'replace_2' => 'Résumé des modifications des projets ANR',
			'read'      => 'Fiche d\'un projet ANR'
		);

		$this->_info = array (
			'info_1' => 'Les modifications du projet ANR ? ont été prises en compte',
			'info_2' => 'La réindexation des données peut prendre quelques minutes pour être effective lors de la consultation de nos portails.',
			'info_3' => 'Vous essayer de creer un doublon du projet ANR ?',
			'info_4' => 'Le projet ANR ? a été créé',
			'info_5' => 'La réindexation peut prendre quelques minutes, le projet peut apparaitre après quleques minutes sur nos portails.'
		);
		
		$this->_description = array (
			'browse'  => 'Ce module vous permet de consulter la liste des projets ANR.',
			'create'  => 'Ce module vous permet de créer de nouveaux projets ANR.',
			'replace' => 'Ce module vous permet de remplacer des projets ANR non valide par un projet ANR valide.',
			'modify'  => 'Ce module vous permet de modifier un projet ANR existant.'
		);
		
		$this->_name          = 'ProjetANR';
		$this->_class         = 'Ccsd_Referentiels_Anrproject';
		$this->_head_columns  = array ('id', 'titre', 'acronyme', 'reference', 'ACTIONS');
		$this->_columns_solR  = array ("id"=>"docid", "titre"=>"title_s", "acronyme" => "acronym_s", "reference" => "reference_s", "valid" => "valid_s");
		$this->_columns_dB    = array ("id"=>"ANRID", "titre"=>"TITLE", "acronyme" => "ACRONYME", "reference" => "REFERENCE", "valid" => "VALID");
	}
}