<?php

class JournalController extends Aurehal_Controller_Referentiel
{
	public function init ()
	{
		$this->_title = array(
			'browse'    => 'Consultation des revues',
			'modify_1'  => 'Modification d\'une revue',
			'modify_2'  => 'Revue modifiée',
            'create_1'  => 'Création d\'une revue',
			'create_2'  => 'Impossible de créer la revue',
			'create_3'  => 'Revue créée',
			'replace_1' => 'Remplacement de revues',
			'replace_2' => 'Résumé des modifications des revues',
			'read'     => 'Fiche d\'une revue'
		);
	
		$this->_info = array (
			'info_1' => 'Les modifications de la revue ? ont été prises en compte',
			'info_2' => 'La réindexation des données peut prendre quelques minutes pour être effective lors de la consultation de nos portails.',
			'info_3' => 'Vous essayer de creer un doublon de la revue ?',
			'info_4' => 'La revue ? a été créée dans le référentiel',
			'info_5' => 'La réindexation peut prendre quelques minutes, la revue peut apparaitre après quelques minutes sur nos portails.'
		);
	
		$this->_description = array (
			'browse'  => 'Ce module vous permet de consulter la liste des revues.',
			'create'  => 'Ce module vous permet de créer de nouvelles revues.',
			'replace' => 'Ce module vous permet de remplacer des revues non valides par une revue valide.',
			'modify'  => 'Ce module vous permet de modifier une revue existante.'
		);
		
		$this->_name          = 'Revue';
		$this->_class         = 'Ccsd_Referentiels_Journal';
		$this->_head_columns = array ('id', 'titre', 'issn', 'editeur', 'ACTIONS');
		$this->_columns_solR = array("id"=>"docid", "titre"=>"title_s", "issn" => "issn_s", "editeur" => "publisher_s", "valid" => "valid_s");
		$this->_columns_dB   = array("id"=>"JID", "titre"=>"JNAME", "issn" => "ISSN", "editeur" => "PUBLISHER", "valid" => "VALID");
	}
}