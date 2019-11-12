<?php

/**
 * Structure
 *
 */
class Hal_Document_Structure extends Ccsd_Referentiels_Structure implements Iterator
{

	/* @var $_solrCorresp array*/
	protected $_solrCorresp = array(
		"docid" 	=> "structid",
		"valid_s" 	=> "valid",
		"acronym_s" => "sigle",
		"name_s" 	=> "structname",
		"address_s" => "address",
		"country_s" => "paysid",
		"url_s" 	=> "url",
		"type_s" 	=> "typestruct"
	);

	//
	public function getForm($extended = true, $populate = true, $id = 0) //permet d'etre compatible avec Ccsd_Referentiels_Abstract::getForm
	{
		return parent::getForm($extended, $populate, $id);
	}
	
	public function initForm ()
	{
		parent::initForm();
	
		$this->_form->addElement('text', 'code', array(
				'label' => 'Code'
		));
	
		$this->_form->getElement('TYPESTRUCT')->setRequired(true);
        $this->_form->getElement('TYPESTRUCT')->setValue("institution");
		$this->_form->getElement('STRUCTNAME')->setRequired(true);
		$this->_form->getElement('SIGLE')->setRequired(false);
		$this->_form->getElement('PAYSID')->setRequired(true);		
		$this->_form->removeElement('VALID');
		$this->_form->addElement('hidden', 'VALID');
		
		$this->_form->getElement('TYPESTRUCT')->setOrder(1);
		$this->_form->getElement('STRUCTNAME')->setOrder(2);
		$this->_form->getElement('SIGLE')->setOrder(3);
		$this->_form->getElement('URL')->setOrder(4);
		$this->_form->getElement('ADDRESS')->setOrder(5);
		$this->_form->getElement('PAYSID')->setOrder(6);
		$this->_form->getElement('STRUCTID')->setOrder(7);
		$this->_form->getElement('VALID')->setOrder(8);
		$this->_form->getElement('code')->setOrder(9);
	}

	public static function search($q, $format = 'json', $type = null, $labelReturn = 'label_html', $nbResultats =100)
	{
		$queryString = "fl=docid," . $labelReturn . "&q=" . urlencode(addcslashes($q, '+-&|!(){}[]^"~*?:\\')) . "&qf=text_autocomplete%20acronym_t^2&defType=edismax&omitHeader=true&rows=" . $nbResultats. "&wt=" . $format . "&sort=" . urlencode('valid_s desc,score desc,label_s asc');
		if ($type != null) {
			$queryString  .= '&fq=type_s:' . urlencode($type);
		}
		return Ccsd_Tools::solrCurl($queryString, 'ref_structure');
	}

    /*
     * Remplace une structure par une autre dans les documents
     */
    static public function replace ($from = 0, $to = 0)
    {
        try {
            if ( $from == 0 || $to == 0 ) {
                return false;
            }
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $where['STRUCTID = ?'] = (int)$from;
            // affiliation auteur/structure
            $db->update(Hal_Document_Author::TABLE_DOCAUTHSTRUCT, array('STRUCTID' => $to), $where);
            // organisme d'appartenance dans la forme auteur
            $sql = $db->select()->from('REF_AUTHOR')->where('STRUCTID = ?', $from);
            foreach ($db->fetchAll($sql) as $author) {
                try {
                    $where['AUTHORID = ?'] = (int)$author['AUTHORID'];
                    $db->update('REF_AUTHOR', array('STRUCTID' => $to), $where);
                } catch (Exception $e) {
                    if ($e->getCode() == '23000') { // une forme auteur existe déjà avec cet organisme
                        $o = new Ccsd_Referentiels_Author(0, ['IDHAL'=>$author['IDHAL'], 'LASTNAME'=>$author['LASTNAME'], 'FIRSTNAME'=>$author['FIRSTNAME'], 'MIDDLENAME'=>$author['MIDDLENAME'], 'EMAIL'=>$author['EMAIL'], 'URL'=>$author['URL'], 'STRUCTID'=>$to]);
                        $sql = $db->select()->from('REF_AUTHOR', 'AUTHORID')->where('MD5 = ?', new Zend_Db_Expr('UNHEX("' . $o->getMd5() . '")'));
                        $newid = $db->fetchOne($sql);
                        if ($newid !== false) {
                            if ( Hal_Document_Author::replace($author['AUTHORID'], $newid) ) {
                                $db->delete('REF_AUTHOR', 'AUTHORID = ' . $author['AUTHORID']);
                            }
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
            }
        } catch (Exception $e) {return false;}
    }

	/**
	 * Initialisation d'une structure à partir d'un retour solR
	 *
	 * @param int
	 * @return bool
	 */
	public function loadFromSolr($structid)
	{
		$solrResponse = unserialize(Ccsd_Tools::solrCurl('q=docid:' . $structid . '&wt=phps&omitHeader=true', 'ref_structure'));
		$dataStructure = Ccsd_Tools::ifsetor($solrResponse['response']['docs'][0], false);
		if ($dataStructure === false) {
			return false;
		}
		foreach($this->_solrCorresp as $fieldSolr => $attrib) {
			if (isset($dataStructure[$fieldSolr])) {
				$this->{'_' . $attrib} = $dataStructure[$fieldSolr];
			}
		}
		//Récupération des parents
		if (isset($dataStructure['parentDocid_i']) && is_array($dataStructure['parentDocid_i'])) {
			foreach ($dataStructure['parentDocid_i'] as $i => $parentStructid) {
				$parentStructure = new self();
				if ($parentStructure->loadFromSolr($parentStructid)) {
					$this->addParent($parentStructure, isset($dataStructure['code_s'][$i]) ? $dataStructure['code_s'][$i] : '');
				}
			}
		}
		return true;
	}
	
	// ITERATORS FUNCTIONS
		
	/**
	 * Retourne la structure actuelle
	 * @see Iterator::current()
	 */
	public function current ()
	{
		return current($this->_parents);
	}
	
	/**
	 * Passe à la structure suivante
	 * @see Iterator::next()
	 */
	public function next ()
	{
		return next($this->_parents);
	}
	
	/**
	 * Retourne l'indice de la structure actuelle
	 * @see Iterator::key()
	 */
	public function key ()
	{
		return key($this->_parents);
	}
	
	/**
	 * Retourne si la structure actuelle est valide
	 * @see Iterator::valid()
	 */
	public function valid ()
	{
		return key($this->_parents) !== null;
	}
	
	/**
	 * Rembobine l'itération sur les structures
	 * @see Iterator::rewind()
	 */
	public function rewind ()
	{
		return reset($this->_parents);
	}

    public function __toString()
    {
        $str = $this->getStructname();
        if ($this->getSigle() != '') {
            $str =  $this->getSigle() . ' - ' . $str;
        }
        if ($this->hasParent()) {
            foreach($this->getParents() as $parent) {
                $str .=  ' - ' . $parent['struct'];
            }
        }
        return $str;
    }


}