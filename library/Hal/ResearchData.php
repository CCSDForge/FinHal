<?php

/**
 * Created by PhpStorm.
 * User: bblondelle
 * Date: 20/10/17
 * Time: 09:49
 */
class Hal_ResearchData implements Hal_Model
{
    const TABLE_RESEARCHDATA = 'DOC_RESEARCHDATA'; // Table des liens vers RESEARCHDATA

    const BASEURL = 'http://api.scholexplorer.openaire.eu/v2/Links/?sourcePid=';

    // Types de nos identifiants
    const TYPE_DOI   = 'doi';

    const DOITYPE_OTHER = 'doi';
    const DOITYPE_UNKNOWN = 'unknown'; // ne devrait pas servir!

    const DOI_URL = 'https://doi.org/';

    const NOUPD    = 1;      //return for retreiveSource for new reference
    const MAJ      = 2;      //return for retreiveSource for updated reference
    const SAME     = 3;      // No change
    const NOTFOUND = 0;      //return for retreiveSource when no source is found

    protected $_idType = self::DOITYPE_UNKNOWN;
    /**
     * @var string : identifiant (Doi, ....
     */
    protected $_dataid = '';
    /**
     * @var string : Source correspondante a l'identifiant
     */
    protected $_source = '';
    /**
     * @var string : Titre de la source correspondante a l'identifiant
     */
    protected $_title = '';
    /**
     * @var DateTime : Date de la source correspondante a l'identifiant
     */
    protected $_date = NULL;
    /**
     * @var string : Publisher de la source correspondante a l'identifiant
     */
    protected $_publisher = '';

    /**
     * Hal_Document_Meta_Researchdata constructor.
     * @param string $source
     * @param string $title
     * @param string $id
     * @param string $publisher
     * @param string $date
     * @param string $idtype
     */
    public function __construct($idtype, $id, $source, $title, $publisher, $date) {
        $this -> _idType = $idtype;
        $this -> _dataid = $id;
        $this -> setSource($source);
        $this -> setTitle($title);
        $this -> _publisher = $publisher;
        $this -> setDate($date);
    }

    /**
     * Supprime une ligne dans la table DOC_RESEARCHDATA par rapport au DATAID
     */
    public function delete()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $db->delete(self::TABLE_RESEARCHDATA, 'DATAID = "' . $this->getDataid(). '"');
    }

    /**
     * @param string $id
     * @return string
     */
    static public function id2type($id) {
        if (substr_compare($id, '10.', 0,3) == 0) {
            return self::TYPE_DOI;
        }
        return null;
    }
    /**
     * Retourne la source correspondant a l'identifiant
     * @param string   $dataid
     * @param string $type.  si le type ne peut etre trouve automatiquement, il est possible de le passer
     * @return Hal_ResearchData|null
     */
    static public function load($dataid, $type = null)
    {
        if ($type === null) {
            // on tente de determiner automatiquement le type d'identifiant
            $type = self::id2type($dataid);
        }
        switch ($type ) {
            case self::TYPE_DOI:
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                $sql = $db->select()->from(self::TABLE_RESEARCHDATA)->where('DATAID = ?', $dataid);
                $result = $db->fetchRow($sql);
                $obj = null;
                if ($result) {
                    $source = $result['SOURCE'];
                    $title = $result['TITLE'];
                    $date = $result['DATE'];
                    $publisher = $result['PUBLISHER'];
                    $obj = new self('doi',  $dataid, $source, $title, $publisher, $date);
                } else {
                    $obj = new self($type, $dataid, '', '', '',NULL);
                }
                break;
            default:
                Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Type d'identifiant: $type non gere");
                // On ne va pas en base, mais on rend un objet plutot vide
                return new self($type, $dataid,'','', '', NULL);
        }
        return $obj;
    }

    /**
     * @throws Zend_Db_Adapter_Exception
     */
    public function save()
    {
        $idType = $this -> getIdtype();
        switch ($idType) {
            case self::TYPE_DOI:
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();

                $sql = $db->select()->from(self::TABLE_RESEARCHDATA)->where('DATAID = ?', $this->getDataid());
                $result = $db->fetchRow($sql);

                if ($result != null) {
                    if ($result['SOURCE'] != $this->getSource()) { // Vérifie si le lien source pour un ID a changé
                        $db->update(self::TABLE_RESEARCHDATA, array('SOURCE' => $this->getSource()), 'DATAID = "' . $this->getDataid() . '"');
                    } else if ($result['TITLE'] != $this->getTitle()) {
                        $db->update(self::TABLE_RESEARCHDATA, array('TITLE' => $this->getTitle()), 'DATAID = "' . $this->getDataid() . '"');
                    } else if ($result['DATE'] != $this->getDate()) {
                        $db->update(self::TABLE_RESEARCHDATA, array('DATE' => $this->getDate()), 'DATAID = "' . $this->getDataid() . '"');
                    } else if ($result['PUBLISHER'] != $this->getPublisher()) {
                        $db->update(self::TABLE_RESEARCHDATA, array('PUBLISHER' => $this->getPublisher()), 'DATAID = "' . $this->getDataid() . '"');
                    }
                } else { // Vérifie qu'un lien source n'existe pas déjà
                    $bind = array(
                        'DATAID' => $this->getDataid(),
                        'SOURCE' => $this->getSource(),
                        'TITLE' => $this->getTitle(),
                        'PUBLISHER' => $this->getPublisher(),
                        'DATE' => $this->getDate()
                    );
                    $db->insert(self::TABLE_RESEARCHDATA, $bind);
                }
                break;
            default:
                Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Type d'identifiant: $idType non gere");
                return false;
        }
        // sauvegarde effectuee si necessaire
        return true;
    }

    /**
     * @param array $schollixInfo
     * @param bool $update
     * @return bool
     */
    public function retreiveInfo($schollixInfo, $update = false) {
        $oldsource = $source = $this->getSource();
        $olddate = $date = $this->getDate();
        $oldtitle = $title = $this->getTitle();
        $publisher = $this->getPublisher();
        $idSite = $this->getIdtype();

        if ($source === null || $source == '' || $update) {
            switch ($idSite) {
                case self::TYPE_DOI:
                    $source = $schollixInfo['source'];
                    $date = $schollixInfo['date'];
                    $title = $schollixInfo['title'];
                    $publisher = $schollixInfo['publisher'];
                    break;
                default:
                    $source = '';
                    $date = NULL;
                    $title = '';
                    $publisher = '';
            }
        }
        if (strlen($source) > 500) { // Pour éviter un crash SQL qui stop le script
            $source = '';
        }

        if ($source != '') {
            if ($source !== $oldsource || $date !== $olddate || $title !== $oldtitle ) {
                $this->setPublisher($publisher);
                $this->setDate($date);
                $this->setTitle($title);
                $this->setSource($source);

                try {
                    $this->save();
                } catch (Zend_Db_Adapter_Exception $e) {
                    // pas de reussite vu que la db a plante...
                    return self::NOUPD;
                }
                // On a trouve qq chose de positif...
                return self::MAJ;
            } else {

                // Pas de mise a jour...
                return self::SAME;
            }
        } else {
            return self::NOTFOUND;
        }
    }

    /**
     * @param stdClass $json : un tableau correspondant au json rendu par l'API LE champs identifiers untiquement
     * @return string[]  couple (doi, autre identifiant)
     */
    public function getDoi($json) {
        $doi='';
        foreach ($json as $typedIdent) {
            if ($typedIdent -> IDScheme  == 'doi') {
                $doi = $typedIdent -> ID;
                continue;
            }
            if ($typedIdent -> IDScheme  == 'D-Net Identifier') {
                continue;
            }
        }
        return $doi;
    }
    /**
     * @param stdClass $publishers : un tableau correspondant au json rendu par l'API LE champs identifiers untiquement
     * @return string[]
     */
    public function getPublishers($publishers) {
        $res = [];
        foreach ($publishers as $publisher) {
            $res [] = $publisher->name;
        }
        return $res[0];
    }

    /**
     * @param stdClass  $json:  tableau de retour de l'API
     * @return array
     */
    public function getSchollixInfo($json) {
        $target = $json -> target;
        $dataDoi = $this -> getDoi($target -> Identifier);
        $date = $target -> PublicationDate;
        $title = (isset($target -> Title)) ? $target -> Title : '';
        $publishersName = (isset($target->Publisher) && !empty($target->Publisher))  ? $this -> getPublishers($target->Publisher) : '';
        $res = [ 'source' => $dataDoi, 'date' => $date, 'title' => $title, 'publisher' => $publishersName];
        return $res;
    }

    /**
     * Définition du dataid
     * @param string $dataid
     */
    public function setDataid($dataid)
    {
        $this->_dataid = $dataid;
    }

    /**
     * Récupération du dataid
     * @return string
     */
    public function getDataid()
    {
        return $this->_dataid;
    }


    /**
     * Récupération de la source
     * @return string
     */
    public function getSource()
    {
        return $this-> _source;
    }

    /**
     * @param $source
     */
    public function setSource($source) {
        $this -> _source = $source;
    }

    /**
     * Récupération du titre
     * @return string
     */
    public function getTitle()
    {
        return $this-> _title;
    }

    /**
     * @param $title
     */
    public function setTitle($title) {
        $this -> _title = $title;
    }

    /**
     * Récupération de la date de la source
     * @return DateTime
     */
    public function getDate()
    {
        return $this-> _date;
    }

    /**
     * @param $date
     */
    public function setDate ($date) {
        if ($date == '') {
            $this->_date = NULL;
        } else {
            $this->_date = $date;
        }
    }

    /**
     * Récupération du publisher de la source
     * @return string
     */
    public function getPublisher()
    {
        if ($this->_publisher != ''){
            return $this->_publisher;
        }
        return null;
    }

    /**
     * @param $publisher
     */
    public function setPublisher ($publisher) {
       $this->_publisher = $publisher;
    }

    /**
     * @var string $doi
     *  @return string Url de la donnée de recherche (DOI)
     */
    static public function getDataUrl($doi)
    {
        return self::DOI_URL . '/' . $doi;
    }

    /**
     * @return string
     */
    public function getIdtype() {
        return $this->_idType;
    }

    /**
     * @return string
     */
    public function getCitation() {
        $source = $this->getSource();
        $title = $this->getTitle();
        $publisher = $this->getPublisher();
        $date = $this->getDate();
        $citation = '';

        //Author

        //Title
        if ($title != '') {
            $citation .= '"'. $title. '"';
            $citation .= ' ';
        }

        //Date
        if ($date != NULL) {
            $citation .= '('. $date->format('Y') .')';
            $citation .= ' ';
        }
        //Publisher
        if ($publisher != '') {
            $citation .= $publisher.'.';
            $citation .= ' ';
        }
        //Source
        if ($source != '') {
            $citation .= 'doi: <a href="'. $this->getDataUrl($source) .'" target="_blank" rel="noopener">'. $source .'</a>.';
        }

        return $citation;
    }
}