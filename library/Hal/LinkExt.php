<?php

/**
 * Created by PhpStorm.
 * User: bblondelle
 * Date: 20/10/17
 * Time: 09:49
 */
class Hal_LinkExt implements Hal_Model
{
    const TABLE_LINKEXT = 'DOC_LINKEXT'; // Table des liens vers LINKEXT

    const linkext = 'LINKEXT';

    // Types de nos identifiants externes
    const TYPE_ARXIV = 'arxiv';
    const TYPE_DOI   = 'doi';
    const TYPE_PMC   = 'pubmedcentral';

    // Les  DOI renvoient parfois au final vers des sites particuliers, on sites ces sites
    const DOITYPE_ARXIV = 'arxiv';
    const DOITYPE_ISTEX = 'istex';
    const DOITYPE_PMC   = 'pubmedcentral';
    const DOITYPE_OTHER = 'openaccess';
    const DOITYPE_UNKNOWN = 'unknown'; // ne devrait pas servir!

    const OADOI_URL = 'https://api.unpaywall.org/v2';
    const ISTEX_URL = 'https://api.istex.fr/document/openurl?rft_id=info:doi/';
    const ARXIV_URL = "http://arxiv.org/pdf/";
    const PMC_URL   = "https://www.ncbi.nlm.nih.gov/pmc/articles/";

    const NOUPD    = 1;      //return for retreiveUrl for new reference
    const MAJ      = 2;      //return for retreiveUrl for updated reference
    const SAME     = 3;      // No change
    const NOTFOUND = 0;      //return for retreiveUrl when no url is found

    protected $_idType = self::DOITYPE_UNKNOWN;
    /**
     * @var string : identifiant (Doi, ArxivId, ....
     */
    protected $_linkid = '';
    /**
     * @var string : Url correspondante a l'identifiant
     */
    protected $_url = '';
    /**
     * Pour les identifiant de type DOI, si l'Url pointe vers Arxiv, ... on le signale comme lien Arxiv pour affichage de logo
     * @var string : Identifiant du type de ressource (arxiv, pubmedcentral, istex, openaccess, unknown....
     */
    protected $_idSite = '';  // Un doi peut envoyer sur Arxiv, linkid
    /**
     * Hal_Document_Meta_LinkExt constructor.
     * @param string $url
     * @param string $id
     * @param string $idtype
     */
    public function __construct($idtype, $id, $url) {
        $this -> _idType = $idtype;
        $this -> _linkid = $id;
        $this -> setUrl($url);
    }

    /**
     * Look at Url to look at well known site
     * @return string
     */
    public function computeIdSite() {
        $idtype = $this->getIdtype();
        $url    = $this->getUrl();
        switch ($idtype) {
            case self::TYPE_ARXIV:
                $idSite = self::DOITYPE_ARXIV;
                break;
            case self::TYPE_PMC:
                $idSite = self::DOITYPE_PMC;
                break;
            case self::TYPE_DOI:
                if (stripos($url, 'api.istex.fr/')) {
                    $idSite = self::DOITYPE_ISTEX;
                } elseif (stripos($url, 'arxiv.org/')) {
                    $idSite = self::DOITYPE_ARXIV;
                } elseif (stripos($url, 'www.ncbi.nlm.nih.gov/pmc')) {
                    $idSite = self::DOITYPE_PMC;
                } else {
                    $idSite = self::DOITYPE_OTHER;
                }
                break;
            default:
                $idSite = self::DOITYPE_UNKNOWN;
        }
        return $idSite;
    }
    /**
     * Supprime une ligne dans la table DOC_LINKEXT par rapport au LINKID
     */
    public function delete()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $db->delete(self::TABLE_LINKEXT, 'LINKID = "' . $this->getLinkid(). '"');
    }
    /**
     * Compute and set IdSite of object
     */
    public function updateIdSite() {
        $idSite = $this ->computeIdSite();
        $this->setIdSite($idSite);
    }

    /**
     * @param $id
     * @return string
     */
    static public function id2type($id) {
        if (substr_compare($id, '10.', 0,3) == 0) {
            return self::TYPE_DOI;
        }
        if (substr_compare($id, 'PMC', 0,3) == 0) {
            return self::TYPE_PMC;

        }
        // C'est plus complex de tester la reconnaissance des id arxiv... alors on prends par defaut...
        // Il faudra peut etre le coder un jour!
        return self::TYPE_ARXIV;
    }
    /**
     * Retourne l'url correspondant a l'identifiant
     * @param string   $linkid
     * @param string $type.  si le type ne peut etre trouve automatiquement, il est possible de le passer
     * @return Hal_LinkExt|null
     */
    static public function load($linkid, $type = null)
    {
        if ($type === null) {
            // on tente de determiner automatiquement le type d'identifiant
            $type = self::id2type($linkid);
        }
        switch ($type ) {
            case self::TYPE_ARXIV:
            case self::TYPE_PMC:
                // pas dans la DB, pas de requete de chargement...
                $obj = new self($type, $linkid,'');
                // pour ces type on calcule de suite la valeur
                $obj->retreiveUrl();
                break;
            case self::TYPE_DOI:
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                $sql = $db->select()->from(self::TABLE_LINKEXT)->where('LINKID = ?', $linkid);
                $result = $db->fetchRow($sql);
                $obj = null;
                if ($result) {
                    $url = $result['URL'];
                    $obj = new self('doi',  $linkid, $url);
                    $obj->updateIdSite();
                } else {
                    $obj = new self($type, $linkid, '');
                }
                break;
            default:
                Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Type d'identifiant: $type non gere");
                // On ne va pas en base, mais on rend un objet plutot vide
                return new self($type, $linkid,'');
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
            case self::TYPE_ARXIV:
            case self::TYPE_PMC :
                // Les linkext arxiv et pmc sont calcules, pas besoin de les sauver...
                break;
            case self::TYPE_DOI:
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();

                $sql = $db->select()->from(self::TABLE_LINKEXT)->where('LINKID = ?', $this->getLinkid());
                $result = $db->fetchRow($sql);

                $sqlurl = $db->select()->from(self::TABLE_LINKEXT)->where('URL = ?', $this->getUrl());
                $resulturl = $db->fetchOne($sqlurl);

                if ($result != null) {
                    if ($result['URL'] != $this->getUrl()) { // Vérifie si le lien url pour un ID a changé
                        $db->update(self::TABLE_LINKEXT, array('URL' => $this->getUrl()), 'LINKID = "' . $this->getLinkid() . '"');
                    }
                } else if ($resulturl == null) { // Vérifie qu'un lien url n'existe pas déjà
                    $bind = array(
                        'LINKID' => $this->getLinkid(),
                        'URL' => $this->getUrl()
                    );
                    $db->insert(self::TABLE_LINKEXT, $bind);
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
     * @param bool $update
     * @return bool
     */
    public function retreiveUrl($update = false) {
        $oldurl = $url = $this->getUrl();
        $idvalue = $this->getLinkid();
        $idSite = $this->getIdtype();
        if ($url === null || $url == '' || $update) {
            switch ($idSite) {
                case self::TYPE_ARXIV:
                    $url = self::getUrlArxivFromId($idvalue);
                    break;
                case self::TYPE_PMC:
                    $url = self::getUrlPMCFromId($idvalue);
                    break;
                case self::TYPE_DOI:

                    $url = self::getUrlFromOADoi($idvalue);
                    if ($url === null) {
                        $url = self::getUrlIstexFromDoi($idvalue);
                    }
                    break;
                default:
                    $url = '';
            }
        }
        if (strlen($url) > 500) { // Pour éviter un crash SQL qui stop le script
            $url = '';
        }
        if ($url !='') {
            if ($url !== $oldurl) {
                $this->setUrl($url);
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
     * Récupère l'url istex à partir d'un DOI
     * @param string $doi
     * @return string|null
     */

    static private function getUrlIstexFromDoi ($doi)
    {
        $url = self::ISTEX_URL . $doi . '&noredirect&sid=hal';
        try {
            $result = self::curl($url);
            if (is_array($result) && (array_key_exists('resourceUrl', $result))) {
                return $result['resourceUrl'];
            }
        } catch (Exception $e) {
            // Ok : pas trouve... suite a erreur curl
        }
        return null;
    }

    /**
     * Get URL (Open Access) from Doi, by cURL on oaDoi
     * @param string $doi
     * @return null|string
     */
    static private function getUrlFromOADoi($doi)
    {

        if (!isset($doi)) {
            return null;
        }

        $doi = trim($doi);

        try {
            $oadoi = self::curl(self::OADOI_URL . "/" . urlencode($doi) . '?email=ccsd-tech@ccsd.cnrs.fr');

            if (!isset($oadoi['best_oa_location']['url'])) {
                return null;
            }

            return $oadoi['best_oa_location']['url'];
        } catch (Exception $e ) {
            // Ok : pas trouve... suite a erreur curl
            return null;
        }
    }

    /**
     * Récupère l'url arxiv à partir d'un ArxivId
     * @param string $id
     * @return string
     */

    static private function getUrlArxivFromId ($id)
    {
        $url = self::ARXIV_URL . $id;

        return $url;
    }

    /**
     * Récupère l'url PMC à partir d'un PMCID
     * @param string $id
     * @return string
     */

    static private function getUrlPMCFromId ($id)
    {
        $url = self::PMC_URL . $id . '/pdf';

        return $url;
    }

    /**
     * @param string $url
     * @return mixed
     * @throws Exception
     */
    static public function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "HAL CCSD OA finder ccsd-tech@ccsd.cnrs.fr");

        $result = curl_exec($ch);

        if (curl_errno($ch) == CURLE_OK) {
            curl_close($ch);
            return json_decode($result,true);
        } else {
            $errno = curl_errno($ch);
            $error_message = curl_strerror($errno) . '. Query: ' . $url;
            curl_close($ch);
            throw new Exception("cURL error ({$errno}): {$error_message}", $errno);
        }
    }

    /**
     * Définition du linkid
     * @param string $linkid
     */
    public function setLinkid($linkid)
    {
        $this->_linkid = $linkid;
    }

    /**
     * Récupération du linkid
     * @return string
     */
    public function getLinkid()
    {
        return $this->_linkid;
    }


    /**
     * Récupération de l'url
     * @return string
     */
    public function getUrl()
    {
        return $this-> _url;
    }

    /**
     * @param $url
     */
    public function setUrl($url) {
        $this -> _url = $url;
        $this->updateIdSite();
    }
    /**
     * @param string $code
     */
    public function setIdSite($code) {
        $this -> _idSite = $code;
    }
    /**
     * @return string
     */
    public function getIdSite() {
        return $this->_idSite;
    }

    /**
     * @return string
     */
    public function getIdtype() {
        return $this->_idType;
    }
}