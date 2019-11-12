<?php

/**
 * Created by PhpStorm.
 * User: bblondelle
 * Date: 20/10/17
 * Time: 09:49
 */
class Hal_Document_Meta_Researchdata extends Hal_Document_Meta_Object
{
    /** TODO : Objet ResearchData */

    /** @var string  */
    const researchdata = 'researchdata';

    /**
     * Hal_Document_Meta_Researchdata constructor.
     * @param string $url
     * @param string|Hal_ResearchData $doiOrResearchData
     * @param $source
     * @param $uid
     * @param int $status
     */
    public function __construct($key, $doiOrResearchData, $group, $source, $uid, $status = 0) {
        parent::__construct(self::researchdata, $doiOrResearchData, $group, $source, $uid, $status);

        if ($doiOrResearchData instanceof Hal_ResearchData) {
            $this->_value = $doiOrResearchData;
        } else {
            // Just a doi
            $this->_value = Hal_ResearchData::load($doiOrResearchData);
        }
    }

    /**
     * @param string $name
     * @param Hal_Document_Meta_Researchdata[] $links
     * @return Hal_Document_Meta_Researchdata
     */
    static public function getHasCopyByName($name, $links) {
        if (array_key_exists($name, $links)) {
            $linkObj = $links[$name];
            if (isset($linkObj)) {
                return $linkObj;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getSource() {
        /** @var Hal_ResearchData $researchdataObj */
        $researchdataObj = $this->_value;
        if ($researchdataObj instanceof Hal_ResearchData) {
            return $researchdataObj -> getSource();
        }
        return null;
    }

    /**
     * @return string
     */
    public function getCitation() {
        /** @var Hal_ResearchData $researchdataObj */
        $researchdataObj = $this->_value;
        if ($researchdataObj instanceof Hal_ResearchData) {
            return $researchdataObj -> getCitation();
        }
        return null;
    }

    /**
     * @return null|string
     */
    public function getIdSite() {
        /** @var Hal_ResearchData $researchdataObj */
        $researchdataObj = $this->_value;
        if ($researchdataObj instanceof Hal_ResearchData) {
            return $researchdataObj -> getIdSite();
        }
        return null;
    }
    /**
     * @return null|string
     */
    public function getIdtype() {
        /** @var Hal_ResearchData $researchdataObj */
        $researchdataObj = $this->_value;
        if ($researchdataObj instanceof Hal_ResearchData) {
            return $researchdataObj -> getIdtype();
        }
        return null;
    }
    /**
     * Récupère l'url istex à partir d'un DOI
     * @param Hal_Document_Meta_Researchdata[] $links
     * @return Hal_Document_Meta_Researchdata
     */
    static public function getPrioritaryLink($links) {

        if ($obj = self::getHasCopyByName(Hal_ResearchData::TYPE_DOI, $links)) {
            return $obj;
        }
        // Pas de lien externe accepte comme source de lien archives ouvertes
        return null;
    }

    /**
     * @param int[] $metaids
     * @param $docid
     * @param $sid
     */
    public function insertLine(&$metaids, $docid, $sid)
    {
        // No DB, no save of meta... so no insert
    }

    /**
     * @param Hal_Document_Meta_Identifier $identifiers
     * @return Hal_Document_Meta_Researchdata
     */
    static public function load($identifiers) {
        $values = $identifiers -> getValue();
        // $values est un array [type => array de valeur d'identifiant
        $theGoodLinks = self::getPrioritaryLink($values);
        if (null === $theGoodLinks) return null;

        $id = $theGoodLinks;
        $researchdataObj = Hal_ResearchData::load($id);
        if (($researchdataObj !== null) && ($researchdataObj->getSource() != '')) {
            return new self($researchdataObj->getSource(), $researchdataObj,'',0,0);
        }
        // Pas de lien trouve
        return null;
    }

    /**
     * @param string $filter
     * @return array|string
     */
    static public function getDefaultValue($filter='')
    {
        if ($filter == '') {
            return [];
        } else {
            return '';
        }
    }


    /**
     *
     * @return string
     */
    public function getValue($group = false)
    {
        /** @var Hal_ResearchData $researchdataObj */
        $researchdataObj = $this->_value;
        if ($researchdataObj instanceof Hal_ResearchData) {
            return $researchdataObj->getDataid();
        }
        return null;
    }

    /**
     * @param int $docid    // Arg standard de Meta_abstract
     * @param int   $sid
     * @param array $metaids
     */
    public function save($docid, $sid, &$metaids = null)
    {
        // La meta est calcule a partir des identifiants externes
        // elle n'est pas sauvegardee
    }

}