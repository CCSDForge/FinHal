<?php

/**
 * Created by PhpStorm.
 * User: bblondelle
 * Date: 20/10/17
 * Time: 09:49
 */
class Hal_Document_Meta_LinkExt extends Hal_Document_Meta_Object
{
    const linkext = 'LINKEXT';

    /**
     * Hal_Document_Meta_LinkExt constructor.
     * @param string $key
     * @param string|Hal_Linkext $doiOrLinkext
     * @param $source
     * @param $uid
     * @param int $status
     */
    public function __construct($key, $doiOrLinkext, $source, $uid, $status = 0) {
        parent::__construct(self::linkext, $key, $doiOrLinkext, $source, $uid, $status);

        if ($doiOrLinkext instanceof Hal_LinkExt) {
            $this->_value = $doiOrLinkext;
        } else {
            // Just a doi
            $this->_value = Hal_LinkExt::load($doiOrLinkext);
        }
    }

    /**
     * @param string $name
     * @param Hal_Document_Meta_LinkExt[] $links
     * @return Hal_Document_Meta_LinkExt
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
    public function getUrl() {
        /** @var Hal_LinkExt $linkextObj */
        $linkextObj = $this->_value;
        if ($linkextObj instanceof Hal_LinkExt) {
            return $linkextObj -> getUrl();
        }
        return null;
    }

    /**
     * @return null|string
     */
    public function getIdSite() {
        /** @var Hal_LinkExt $linkextObj */
        $linkextObj = $this->_value;
        if ($linkextObj instanceof Hal_LinkExt) {
            return $linkextObj -> getIdSite();
        }
        return null;
    }
    /**
     * @return null|string
     */
    public function getIdtype() {
        /** @var Hal_LinkExt $linkextObj */
        $linkextObj = $this->_value;
        if ($linkextObj instanceof Hal_LinkExt) {
            return $linkextObj -> getIdtype();
        }
        return null;
    }
    /**
     * Récupère l'url istex à partir d'un DOI
     * @param Hal_Document_Meta_LinkExt[] $links
     * @return Hal_Document_Meta_LinkExt
     */
    static public function getPrioritaryLink($links) {

        if ($obj = self::getHasCopyByName(Hal_LinkExt::TYPE_ARXIV, $links)) {
            return $obj;
        }
        if ($obj = self::getHasCopyByName(Hal_LinkExt::TYPE_PMC, $links)) {
            return $obj;
        }
        if ($obj = self::getHasCopyByName(Hal_LinkExt::TYPE_DOI, $links)) {
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
     * @return Hal_Document_Meta_LinkExt
     */
    static public function load($identifiers) {
        $values = $identifiers -> getValue();
        // $values est un array [type => array de valeur d'identifiant
        $theGoodLinks = self::getPrioritaryLink($values);
        if (null === $theGoodLinks) return null;

        $id = $theGoodLinks;
        $linkextObj = Hal_LinkExt::load($id);
        if (($linkextObj !== null) && ($linkextObj-> getUrl() != '')) {
            return new self($linkextObj->getUrl(), $linkextObj,'',0,0);
        }
        // Pas de lien trouve
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