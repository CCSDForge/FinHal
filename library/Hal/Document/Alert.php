<?php

/**
 *
 * Created by PhpStorm.
 * User: yannick
 * Date: 25/10/2016
 * Time: 13:57
 */
class Hal_Document_Alert
{
    /**
     * @var Hal_Document null
     */
    protected $_document = null;

    protected $_coauthors = [];

    protected $_referents = [];

    protected $_administrators = [];


    public function __construct($document)
    {
        if (!$document instanceof Hal_Document){
            throw new InvalidArgumentException('not instance of Hal_Document');
        }
        $this->_document = $document;
    }

    public function getDocument()
    {
        return $this->_document;
    }

    /**
     * Retourne un tableau d'Uid de tous les co-auteurs d'un document
     * @return array
     */
    public function getCoAuthors()
    {
        $document = $this->getDocument();
        $coauthors = array();

        $contributor = $document->getContributor();
        foreach ($document->getAuthors() as $author){
            if ($author->getIdHal() != null){
                $idhal = $author->getIdHal();
                $coauthors[] = $author->getUidFromIdHal($idhal);
            }
        }
        $coauthors = array_diff($coauthors, (array)$contributor);
        return $coauthors;
    }

    /**
     * Retourne la liste des Référents Structure
     * @return array
     */
    public function getReferents()
    {
        $document = $this->getDocument();
        $structIds = array();
        $where = '';
        foreach ($document->getStructures() as $structure){
            if (!in_array($structure->getStructid(), $structIds)){
                if (!empty($structIds)){
                    $where .= ' OR VALUE = '. $structure->getStructid();
                } else {
                    $where .= 'VALUE = '. $structure->getStructid();
                }
                $structIds[] = $structure->getStructid();
                if (!empty($structure->getAllParents())){ //Récupère les Strucids des parents
                    foreach ($structure->getAllParents() as $parent){
                        $where .= ' OR VALUE = '. $parent['struct']->getStructid();
                    }
                }
            }
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql =  $db->select()
            ->from('USER_RIGHT', 'UID')
            ->where('RIGHTID = ?', 'adminstruct')
            ->where($where);

        return array_unique($db->fetchCol($sql));
    }

    /**
     * Retourne la liste des administrateurs du portail
     * @return array
     */
    public function getAdminPortail()
    {
        if ((defined ( 'SITENAME' )) AND (SITENAME != '')) {
            $portail = SITENAME;
        } else {
            try {
                $portail =  Zend_Registry::get ( 'website' )->getSiteName ();
            } catch ( Exception $e ) {
                $portail = 'HAL';
            }
        }

        if ($portail == '') {
            return 'HAL';
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql =  $db->select()
            ->from(Hal_Site::TABLE, 'SID')
            ->where('SITE = ?', $portail);
        $result = $db->fetchOne($sql);

        $sql =  $db->select()
            ->from('USER_RIGHT', 'UID')
            ->where('RIGHTID = ?', 'administrator')
            ->where('SID = ?', $result);

        return $db->fetchCol($sql);
    }

}