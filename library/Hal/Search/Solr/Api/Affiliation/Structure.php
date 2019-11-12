<?php

/**
 * Class Hal_Search_Solr_Api_Affiliation_Structure
 * API Affiliation d'après le code de Laurence Farhi
 * Gestion des structures
 */
class Hal_Search_Solr_Api_Affiliation_Structure extends Hal_Search_Solr_Api_Affiliation
{

    const MY_ENUM_VALID_TO_VALID = 'TO_VALID';
    const MY_ENUM_VALID_ID_UNKNOWN = 'ID_UNKNOWN'; //on a un id diff de 0 mais non dans le référentiel
    const MY_ENUM_VALID_NO_ID = 'NO_ID'; //on n'a pas de id, que des infos de libelle, acronyme ...
    const MY_ENUM_VALID_ID_VALID = 'ID_VALID';//il y a un id et il est valide
    const MY_ENUM_VALID_ERROR = 'ERROR';
    const MY_ENUM_VALID_ID_TROUVE = 'ID_TROUVE';//NO_ID mais on l'a trouvé par calcul
    const MY_ENUM_STATUS_VALID = 'VALID';
    //ENUM pour le status d'une structure selon son existance dans HAL
    const MY_ENUM_STATUS_OLD = 'OLD';
    const MY_ENUM_STATUS_INCOMING = 'INCOMING';
    static private $_myEnumValidValues = null;


    /**
     * @var integer $idAuteurArticle
     *

     */
    private $idAuteurArticle;


    /**
     * Liste des tutelles = champ calculé à partir du referentiel des structures
     * on duplique l'information mais sinon trop long à l'affichage
     *
     * @var string $tutelles
     */
    private $tutelles;


    /**
     * $docid de la structure = champ docid du referentiel des structures
     * @var integer $docid
     *
     */
    private $docid;

    /**
     * libelle de la structure =
     * --- si on a trouvé la structure dans le référentiel, champ Solr label_s
     * --- sinon nom de la structure trouvé dans le fichier source
     *
     * @var string name
     */
    private $name;

    /**
     * acronyme de la structure trouvée dans le fichier source
     *
     * @var string acronym
     */
    private $acronym;

    /**
     * type de la structure trouvée dans le fichier source
     *
     * @var string type
     */
    private $type;

    /**
     * pays de la structure trouvée dans le fichier source
     *
     * @var string country
     */
    private $country;

    /**
     * status de la structure trouvée dans le fichier source (VALID, OLD, INCOMING)
     *
     * @var string status
     */
    private $status;

    /**
     * Liste des tutelles = champ calculé à partir du referentiel des structures
     * on duplique l'information mais sinon trop long à l'affichage
     *
     * @var string $HALTutelles
     */
    private $HALTutelles;
    /**
     * validite de l'affiliation (par exemple, si docid=9999999 ==> non valide
     * valeurs possible parmi self::MY_ENUM_VALID_xxx
     *
     * @var string valid
     */
    private $valid;


    /**
     * adresse de l'affi
     *
     * @var string addrLine
     */
    private $addrLine;


    /**
     * Hal_Search_Solr_Api_Affiliation_Structure constructor.
     * @param null $HALdocid
     * @param string $name
     * @param null $tutelles
     * @param string $valid
     * @param string $addrLine
     * @param string $acronym
     * @param string $type
     * @param string $country
     * @param string $status
     */
    public function __construct($HALdocid = null, $name = '', $tutelles = null, $valid = self::MY_ENUM_VALID_TO_VALID,
                                $addrLine = null, $acronym = null, $type = null, $country = null, $status = null)
    {
        $this->setDocid($HALdocid);
        $this->setName($name);
        $this->setAcronym($acronym);
        $this->setDocid($HALdocid);
        $this->setType($type);
        $this->setCountry($country);
        $this->setAddrLine($addrLine);
        $this->setStatus($status);
        if ($valid == null)
            $this->valid = self::MY_ENUM_VALID_TO_VALID;
        else
            $this->setValid($valid);
        $this->setHALTutelles($tutelles);
    }

    static public function getMyEnumValidChoices()
    {
        if (self::$_myEnumValidValues == null) {
            self::$_myEnumValidValues = array();
            $oClass = new \ReflectionClass('Hal_Search_Solr_Api_Affiliation_Structure');
            $classConstants = $oClass->getConstants();
            $constantPrefix = "MY_ENUM_VALID_";
            foreach ($classConstants as $key => $val) {
                if (substr($key, 0, strlen($constantPrefix)) === $constantPrefix) {
                    self::$_myEnumValidValues[$val] = $val;
                }
            }
        }
        return self::$_myEnumValidValues;
    }


    /**
     *  recherche une structure  étant donne son id
     * et retourne les infos relative à cette structure
     * sous forme d'une instance de  HALStructure
     * sous forme d'un array('docid' ,'label' ,'tutelles','valid')
     * @param $id
     * @return Hal_Search_Solr_Api_Affiliation_Structure|null
     */
    public static function getLaboComplet($id)
    {

        if (!empty($id)) {
            $query_str = "docid:" . $id;
            $res_array = self::findStructureWithSolr($query_str);

            // set fields to fetch (this overrides the default setting 'all fields')
            if (!empty($res_array)) {
                $struct = $res_array['response']['docs'][0];
                $HALStruct = self::createHALStructureFromResSolr($struct);
                return $HALStruct;
            } else
                $HALAffi = new self($id, "structure not found ($id)", $tutelles = null, self::MY_ENUM_VALID_ID_UNKNOWN);
            return $HALAffi;
        } else
            return null;
    }

    /**
     * @param $query
     * @return mixed
     */
    public static function findStructureWithSolr($query)
    {
        $q['q'] = $query;
        $q['rows'] = 1;
        $q['wt'] = 'phps';
        $q = array_map('trim', $q);
        $query = http_build_query($q);
        $listeDocSolr = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_RefStructure::$_coreName);


        return unserialize($listeDocSolr);
    }

    /**
     * Créé et renvoie une instance de HALStructure
     * à partir d'une structure trouvée en retour de $this->findBy
     * @param $structFromResSolr
     * @return Hal_Search_Solr_Api_Affiliation_Structure|null
     */
    public static function createHALStructureFromResSolr($structFromResSolr)
    {
        $parents = null;

        if (empty($structFromResSolr)) {
            return null;
        }

        $id = $structFromResSolr['docid'];
        $label = $structFromResSolr['label_s'];

        //calcule des tutelles
        if (isset($structFromResSolr['parentName_s'])) {
            $parents = $structFromResSolr['parentName_s'];
        }

        $type = null;
        if (isset($structFromResSolr['type_s']))
            $type = $structFromResSolr['type_s'];
        $country = null;
        if (isset($structFromResSolr['country_s']))
            $country = $structFromResSolr['country_s'];
        $status = null;
        if (isset($structFromResSolr['valid_s']))
            $status = $structFromResSolr['valid_s'];
        $liste_tutelles = '';

        if (is_array($parents)) {
            foreach ($parents as $parent) {
                if (empty($liste_tutelles))
                    $liste_tutelles .= $parent;
                else
                    $liste_tutelles .= ', ' . $parent;
            }
        }
        return new self($id, $label, $liste_tutelles, self::MY_ENUM_VALID_ID_VALID, $addrLine = null, $acronym = null, $type, $country, $status);

    }


    /**
     * Get idAuteurArticle
     *
     * @return integer
     */
    public function getIdAuteurArticle()
    {
        return $this->idAuteurArticle;
    }

    /**
     * Set idAuteurArticle
     *
     * @param integer $idAuteurArticle
     */

    public function setIdAuteurArticle($idAuteurArticle)
    {
        $this->idAuteurArticle = $idAuteurArticle;
    }

    /**
     * Object to Array
     * @return array
     */
    public function toArray()
    {

        $affiliation['docid'] = $this->getDocid();
        $affiliation['name'] = $this->getName();
        $affiliation['acronym'] = $this->getAcronym();
        $affiliation['type'] = $this->getType();
        $affiliation['country'] = $this->getCountry();
        $affiliation['status'] = $this->getStatus();

        return array_filter($affiliation);
    }

    /**
     * Get docid
     *
     * @return integer
     */
    public function getDocid()
    {
        return $this->docid;
    }

    /**
     * Set docid
     *
     * @param integer $docid
     */
    public function setDocid($docid)
    {
        $this->docid = $docid;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAcronym()
    {
        return $this->acronym;
    }

    public function setAcronym($acronym)
    {
        $this->acronym = $acronym;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = strtolower($type);
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = strtolower($country);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get IsAffiliationConnue
     *
     * @return boolean
     * true si l'affiliation est connue
     * false sinon
     */
    public function IsAffiliationConnue()
    {
        if ($this->getValid() == self::MY_ENUM_VALID_ID_VALID) {
            return true;
        }
        return false;
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function setValid($valid)
    {
        $enumValid = self::getMyEnumValidChoices();
        if (!in_array($valid, $enumValid)) {
            $this->valid = self::MY_ENUM_VALID_ERROR;
        } else {
            $this->valid = $valid;
        }
    }

    /**
     * Get IsAffiliationCalculee
     *
     * @return boolean
     * true si l'affiliation a été déjà été calculée
     * false sinon
     */
    public function IsAffiliationCalculee()
    {
        $valid = $this->getValid();

        if ($valid == self::MY_ENUM_VALID_ID_UNKNOWN || $valid == self::MY_ENUM_VALID_NO_ID || $valid == self::MY_ENUM_VALID_ID_TROUVE) {
            return false;
        }
        return true;
    }

    /**
     * @param Hal_Search_Solr_Api_Affiliation_Structure $HALAutAffiSrc
     */
    public function copy(Hal_Search_Solr_Api_Affiliation_Structure $HALAutAffiSrc)
    {
        $this->init($HALAutAffiSrc);
    }

    public function init(Hal_Search_Solr_Api_Affiliation_Structure $HALstruct)
    {
        $this->setDocid($HALstruct->getDocid());
        $this->setName($HALstruct->getName());
        $this->setAcronym($HALstruct->getAcronym());
        $this->setType($HALstruct->getType());
        $this->setCountry($HALstruct->getCountry());
        $this->setStatus($HALstruct->getStatus());
        $this->setValid($HALstruct->getValid());
        $this->setHALTutelles($HALstruct->getTutelles());
        $this->setAddrLine($HALstruct->getAddrLine());
    }

    public function getTutelles()
    {
        return $this->tutelles;
    }

    public function getAddrLine()
    {
        return $this->addrLine;
    }

    public function setAddrLine($addrLine)
    {
        $this->addrLine = $addrLine;
    }

    public function getHALTutelles()
    {
        return $this->HALTutelles;
    }

    public function setHALTutelles($HALTutelles)
    {
        if (strlen($HALTutelles) > 255)
            $this->HALTutelles = substr($HALTutelles, 0, 250) . '...';
        else
            $this->HALTutelles = $HALTutelles;
    }

    public function __toString()
    {
        return (string)$this->docid;
    }

    public function getLibelleComplet()
    {
        $str = '';
        if (!empty($this->name)) {
            $str = $this->name;
        }
        if (!empty($this->acronym)) {
            $str .= '[' . $this->acronym . ']';
        }
        if (!empty($this->type)) {
            $str .= ', ' . $this->type;
        }
        if (!empty($this->country)) {
            $str .= ', ' . $this->country;
        }
        if (!empty($this->HALTutelles)) {
            $str .= ' (Tut.: ' . $this->HALTutelles . ')';
        }
        if (!empty($this->addrLine)) {
            $str .= ' - ' . $this->addrLine;
        }
        return $str;
    }

    public function getHALLabel_s()
    {
        return $this->getHALLabelInput();
    }

    public function getHALLabelInput()
    {
        if (!empty($this->name)) {
            $str = $this->name;
        }
        if (!empty($this->acronym)) {
            $str .= '[' . $this->acronym . ']';
        }
        if (!empty($this->type)) {
            $str .= ' - ' . $this->type;
        }

        return $str;
    }

    /**
     * retourne AuteurArticle::MY_ENUM_ETAT_CALC_xxx (proba) s'il s'agit d'une structure similaire
     * false sinon
     * @param $name
     * @param $acronym
     * @return string
     */
    public function isSimilarStruct($name, $acronym)
    {
        $currentName = Hal_Search_Solr_Api_Affiliation::drop_accent_and_lower($this->getName());
        $currentAcronym = Hal_Search_Solr_Api_Affiliation::drop_accent_and_lower($this->getAcronym());
        $name = Hal_Search_Solr_Api_Affiliation::drop_accent_and_lower($name);
        $acronym = Hal_Search_Solr_Api_Affiliation::drop_accent_and_lower($acronym);
        if (!is_null($acronym)) {
            //même acronyme
            if (strpos($acronym, $currentAcronym) !== false)
                return Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_CALC_CERTAIN;
            //acronyme=nom
            elseif (strpos($acronym, $currentName) !== false)
                return Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_CALC_TRES_PROBABLE;
        }
        if (!is_null($name)) {
            if (strpos($name, $currentName) !== false)
                return Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_CALC_CERTAIN;
            elseif (strpos($name, $currentAcronym) !== false)
                return Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_CALC_TRES_PROBABLE;

        }
    }

    /**
     * Recherche d'une structure dans le référentiel à partir d'un objet HALStructure
     * retourne la structure si trouvé, null sinon
     * @param Hal_Search_Solr_Api_Affiliation_Structure $HALStructure
     * @return Hal_Search_Solr_Api_Affiliation_Structure | null
     */
    public function findByHALStructure(Hal_Search_Solr_Api_Affiliation_Structure $HALStructure)
    {
        $res_array = null;
        $res = null;
        $affiName = $HALStructure->getName();
        $affiAcronym = $HALStructure->getAcronym();
        //si nom et acronyme sont vide, on quitte
        if (empty($affiName) && empty($affiAcronym))
            return null;
        $listSimilarLabels = $HALStructure->getAllSimilarLabels(); //tableau de tous les libelles nom, acronymes, ... sans les ponctuations, en recuperant les(xxx), et [yyy]
        $type = $HALStructure->getType();
        $country = $HALStructure->getCountry();
        $tutelles = $HALStructure->getTutelles();
        //$libelleTutelle_s='';
        //calcul de la partie de requete pour les tutelles
        $libelleTutelle_t = '';
        if (!empty($tutelles)) {
            $tutellesListe = explode(',', $tutelles);
            foreach ($tutellesListe as $tutelle) {
                //$lib_s = "parentAcronym_s:\"$tutelle\" OR  parentName_s:\"$tutelle\"";
                $lib_t = "parentAcronym_t:\"$tutelle\" OR  parentName_t:\"$tutelle\"";
                if (empty($libelleTutelle_t)) {
                    //$libelleTutelle_s = $lib_s;
                    $libelleTutelle_t = $lib_t;
                } else {
                    //$libelleTutelle_s .= " OR $lib_s";
                    $libelleTutelle_t .= " OR $lib_t";
                }
            }
            //$libelleTutelle_s = "($libelleTutelle_s)";
            $libelleTutelle_t = "($libelleTutelle_t)";
        }
        //Recherche sur le nom, acronyme, pays exacte (sans casse)
        if (!is_null($affiAcronym) && $affiAcronym != '' && !is_null($affiName) && $affiName != '') {
            $structQuery_sci = " (acronym_sci:\"$affiAcronym\" AND name_sci:\"$affiName\")";
        } elseif (!is_null($affiName) && $affiName != '') {
            $structQuery_sci = " (acronym_sci:\"$affiName\" OR name_sci:\"$affiName\")";
        } elseif (!is_null($affiAcronym) && $affiAcronym != '') {
            $structQuery_sci = " (acronym_sci:\"$affiAcronym\" OR name_sci:\"$affiAcronym\")";
        }
        $HalStructIncoming = null;
        //on recherche avec les tutelles "contient"
        if (!empty($libelleTutelle_t)) {
            $query_str_tutelle = "$structQuery_sci AND $libelleTutelle_t";
            $HALStruct = $this->findByHALStructureWithQuery($query_str_tutelle, $HALStructure);
            if (!is_null($HALStruct)) {
                if ($HALStruct->getStatus() == 'INCOMING')
                    if (is_null($HalStructIncoming))
                        $HalStructIncoming = $HALStruct;
                    else
                        return $HALStruct;
                else
                    return $HALStruct;

            }
        } else //pas de tutelles
        {
            $HALStruct = $this->findByHALStructureWithQuery($structQuery_sci, $HALStructure);
            if (!is_null($HALStruct)) {
                if ($HALStruct->getStatus() == 'INCOMING')
                    if (is_null($HalStructIncoming))
                        $HalStructIncoming = $HALStruct;
                    else
                        return $HALStruct;
                else
                    return $HALStruct;
            }
        }

        //recherche plus large sur le nom, acronyme  "contient" + pays

        $arrFq = '';
        foreach ($listSimilarLabels as $lib) {
            $arrFq[] = "(acronym_t:$lib) OR name_t:($lib)";
        }
        $structQuery_t = implode(" OR ", $arrFq);

        //on recherche d'abord avec les tutelles exactes s'il y en a
        //on recherche avec les tutelles "contient"
        if (!empty($libelleTutelle_t)) {
            $query_str_tutelle = "$structQuery_t AND $libelleTutelle_t";
            $HALStruct = $this->findByHALStructureWithQuery($query_str_tutelle, $HALStructure);
            if (!is_null($HALStruct)) {
                if ($HALStruct->getStatus() != 'INCOMING') {
                    if (!is_null($HalStructIncoming))//on a trouvé une structure qui matche mais non valide
                    {
                        if ($HALStruct->getStatus() != 'INCOMING') {
                            return $HALStruct;
                        } else
                            return $HalStructIncoming;
                    }
                    return $HALStruct;

                }

                return $HALStruct;
            }
        } else //pas de tutelles
        {
            $HALStruct = $this->findByHALStructureWithQuery($structQuery_t, $HALStructure);
            if (!is_null($HALStruct)) {
                if (!is_null($HalStructIncoming))//on a trouvé une structure qui matche mais non valide
                {
                    if ($HALStruct->getStatus() != 'INCOMING') {
                        return $HALStruct;
                    } else
                        return $HalStructIncoming;
                }
                return $HALStruct;
            }
        }
        //on n'a rien trouvé
        // s'il y avait des tutelles renseignées, il faut chercher sans les tutelles
        //on cherche sans la tutelle
        if (!empty($tutelles)) {
            $HALStruct = $this->findByHALStructureWithQuery($structQuery_sci, $HALStructure);
            if (!is_null($HALStruct)) {
                if ($HALStruct->getStatus() != 'INCOMING') {
                    if (!is_null($HalStructIncoming))//on a trouvé une structure qui matche mais non valide
                    {
                        if ($HALStruct->getStatus() != 'INCOMING') {
                            return $HALStruct;
                        } else
                            return $HalStructIncoming;
                    }
                    return $HALStruct;

                }

                return $HALStruct;
            } else {
                $HALStruct = $this->findByHALStructureWithQuery($structQuery_t, $HALStructure);
                if (!is_null($HALStruct)) {
                    if (!is_null($HalStructIncoming))//on a trouvé une structure qui matche mais non valide
                    {
                        if ($HALStruct->getStatus() != 'INCOMING') {
                            return $HALStruct;
                        } else
                            return $HalStructIncoming;
                    }
                    return $HALStruct;
                }
            }
        }
        return null;
    }

    /**
     * A partir de l'acronyme et du nom de l'affi, calcule tous les libellés possibles pour une recherche large
     * retour : array des libellés
     * @return array
     */
    public function getAllSimilarLabels()
    {

        $name = $this->getName();
        $acronym = $this->getAcronym();
        $arrName = array();
        if (!is_null($name))
            $arrName = self::getStructLabelFromString($name);

        $arrAcronyme = array();
        if (!is_null($acronym))
            $arrAcronyme = self::getStructLabelFromString($acronym);

        $affiLibelleArray = array_merge($arrName, $arrAcronyme);
        return ($affiLibelleArray);

    }

    /**
     * calcule un ensemble de libelle possible de structure à partir d'un chaine de caractères
     * @param $str
     * @return array des libellés
     */
    static public function getStructLabelFromString($str)
    {
        $affiLibelleArray = array();
        $strLowerNoAccent = Hal_Search_Solr_Api_Affiliation::drop_accent_and_lower($str);

        //cas ou $s= xxx (yyy) zzz ou xxx [yyy] zzz --> on extrait zzz car c'est peut être un sigle
        if (preg_match('#[\[,\(](\w+)[\],\)]#', $strLowerNoAccent, $m)) {
            $affiLibelleArray[] = Hal_Search_Solr_Api_Affiliation::cleanStringSolr($m[1]);
        }
        //On enleve tout ce qui est entre crochet et parantheses
        $str_ss_sigle = preg_replace('#[\[,\(].*[\],\)]#', '', $strLowerNoAccent);
        $strClean = Hal_Search_Solr_Api_Affiliation::cleanStringSolr($str_ss_sigle);

        $affiLibelleArray[] = $strClean;
        //Pour certains signes de ponctuation, on coupe(split) le libellé pour récupérer tous les bouts de chaine
        foreach (array(',', ';', ':', '/') as $sign) {
            if (strpos($strLowerNoAccent, $sign) !== false) {
                $arrLib = explode($sign, $strLowerNoAccent);
                foreach ($arrLib as $lib) {
                    $affiLibelleArray[] = trim($lib);
                }
            }
        }
        return ($affiLibelleArray);

    }

    /**
     * Recherche d'une structure dans le référentiel
     *
     * @param string $query requete Solr
     * @param Hal_Search_Solr_Api_Affiliation_Structure structure que l'on cherche
     * @return Hal_Search_Solr_Api_Affiliation_Structure|null
     */
    private function findByHALStructureWithQuery($query = '', Hal_Search_Solr_Api_Affiliation_Structure $HALStructure)
    {

        if (!empty($query)) {
            $res_array = '';
            //on recherche d'abord avec le pays s'il y en @author lfarhi
            $country = $HALStructure->getCountry();
            $type = $HALStructure->getType();
            $fqAnd = '';
            $fqOr = '';
            $fqCountry = '';
            $fqType = '';
            if (!empty($country)) {
                $fqCountry = "country_s:$country";
                $fqAnd = $fqCountry;
                $fqOr = $fqCountry;
            }
            if (!empty($type)) {
                $fqType = "type_s:$type";
                if (!empty($country)) {
                    $fqAnd .= " AND $fqType";
                    $fqOr .= " OR $fqType";
                } else {
                    $fqAnd = "$fqType";
                    $fqOr = "$fqType";
                }
            }

            $res_array = $this->findBy($query, $rows = 10, $fq_str = $fqAnd);
            //si on n'a pas trouvé avec le pays et le type
            //on cherche avec le pays ou le type
            if (empty($res_array) && (!empty($country) || !empty($type))) {
                $res_array = $this->findBy($query, $rows = 10, $fq_str = $fqOr);

            }
            //si on n'a pas trouvé avec le pays ou le type
            //on cherche sans le pays et sans le type
            if (empty($res_array) && (!empty($country) || !empty($type))) {
                $res_array = $this->findBy($query, $rows = 10, $fq_str = '');

            }

            if (!empty($res_array)) {
                //on cherche la meilleure structure

                $structTrouve = $this->searchBestStructure($res_array, $HALStructure);
                if (!is_null($structTrouve)) {
                    $HALStruct = $this->createHALStructureFromResSolr($structTrouve);
                    return $HALStruct;
                }

            }
        }
        return null;

    }

    /**
     * Recherche d'une structure par $query, $rows
     * @param $query_str
     * @param int $rows
     * @param string $fq_str
     * @return array
     */
    public function findBy($query_str, $rows = 10, $fq_str = '')
    {
        if (empty($query_str)) {
            return array();
        }

        $q['q'] = $query_str;
        $q['rows'] = (int)$rows;
        $q['wt'] = 'phps';
        $q['fl'] = 'docid,label_s,valid_s,country_s,parentName_s,type_s,valid_s';
        if (!empty($fq_str)) {
            $q['fq'] = $fq_str;
        }

        $q['sort'] = 'valid_s DESC'; //les validées d'abord : VALID, OLD, INCOMING

        $q = array_map('trim', $q);
        $query = http_build_query($q);

        $listeDocSolr = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_RefStructure::$_coreName);

        return unserialize($listeDocSolr)['response']['docs'];


    }

    /**
     * parmi un tableau de structures renvoyés par l'API, recherche la meilleure
     * dans le tableau $res_array,
     * array (size=6)
     * 'docid' =>
     * 'label_s' =>
     * 'parentName_s' =>
     * classé par type_s et valid_s DESC
     * $type => type de structure
     * $country => pays de structure
     *
     * @param array $res_array
     * @param Hal_Search_Solr_Api_Affiliation_Structure $HALStructure
     * @return mixed|null
     */
    private function searchBestStructure(array $res_array, Hal_Search_Solr_Api_Affiliation_Structure $HALStructure)
    {
        if (is_null($res_array))
            return null;
        $type = $HALStructure->getType();
        if (!empty($type)) //On a le type
        {
            foreach ($res_array as $struct) {
                if ($struct['type_s'] == $type) {
                    return $struct;
                }
            }
        }

        //on n'a rien trouvé, on renvoie la 1ere structure
        return $res_array[0];
    }


}