<?php

/**
 * Class Hal_Search_Solr_Api_Affiliation_Author
 * API Affiliation d'après le code de Laurence Farhi
 * Gestion des auteurs
 */
class Hal_Search_Solr_Api_Affiliation_Author extends Hal_Search_Solr_Api_Affiliation
{

    const MY_ENUM_ETAT_VALID = 'Valide';
    const MY_ENUM_ETAT_NON_VALID = 'NonValide';
    const MY_ENUM_ETAT_CALC = 'Calc';
    const MY_ENUM_ETAT_CALC_CERTAIN = 'Calc_Certain';
    const MY_ENUM_ETAT_COPIE = 'Calc_Certain';
    const MY_ENUM_ETAT_CALC_TRES_PROBABLE = 'Calc_TresProbable';
    const MY_ENUM_ETAT_CALC_PROBABLE = 'Calc_Probable';
    const MY_ENUM_ETAT_CALC_POSSIBLE = 'Calc_Possible';
    const MY_ENUM_ETAT_NON_TROUVE_AVEC_AFFI = 'Non_Trouve_Avec_Affi';
    const MY_ENUM_ETAT_AFF_VIDE = 'Aff_Vide';
    const MY_ENUM_ETAT_NON_CALC = 'Non_Calc';
    const MY_ENUM_ETAT_ID_AFFI_SRC = 'IdAffi_Src';

    //ENUM de la colonne etat
    const MY_ENUM_ETAT_ERREUR = 'Erreur';
    const VAL_MIN_ETAT = 1;
    const VAL_MAX_ETAT = 4;//Calculé dans le fichier source


    static private $ETAT_CALC_ORDONNE = array(
        'Calc_Certain' => 4,
        'Calc_TresProbable' => 3,
        'Calc_Probable' => 2,
        'Calc_Possible' => 1,
        'Non_Calc' => 1,
    );
    static private $_myEnumFieldValues = null;


    /**
     * @var integer $id
     */
    private $id;


    /**
     * identifiant idHal
     * @var string $idHal
     */
    private $idHal;


    /**
     * id de la forme auteur. ce champ n'est pris en compte que si idhal renseigné
     * identifiant docid
     * @var string docid
     *
     */
    private $docid;


    /**
     * @var string $nom
     *

     */
    private $nom;//auteur sans affiliation
    /**
     * @var string $prenom
     *

     */
    private $prenom;//auteur renseigné à partir du fichier en entrée
    /**
     * @var string $autreNom
     *

     */
    private $autreNom;
    /**
     * @var string $email
     *

     */
    private $email;

    /**
     * @var string $emailDomain
     *

     */
    private $emailDomain;
    /**
     * @var string $url
     *

     */
    private $url;
    /**
     * @var integer $organismId
     *

     */
    private $organismId;
    /**
     * @var integer $corresponding
     *
     * 0=non, 1=oui
     */
    private $corresponding;


    /**
     * etat de l'auteur par rapport au calcul de ses affiliations
     * ce champ contient une valeur parmi self::MY_ENUM_ETAT_XXX
     * @var string $etat
     */
    private $etat;

    /**
     * @var array
     */
    private $HALAuteurAffis;

    public function __construct()
    {
        $this->setEtat(self::MY_ENUM_ETAT_NON_CALC);
        $this->corresponding = FALSE;
        $this->HALAuteurAffis = array();
    }


    /**
     * permet de calculer une proba de calcul d'affiliation
     * el la pondérant de $valPonder
     * @param $etatCalc
     * @param $valPonder
     * @return int|string
     */
    static public function calcEtatCalc($etatCalc, $valPonder)
    {
        if (array_key_exists($etatCalc, self::$ETAT_CALC_ORDONNE)) {
            $valEtatCalc = self::$ETAT_CALC_ORDONNE [$etatCalc];
            $valEtatCalc += $valPonder;
            if ($valEtatCalc < self::VAL_MIN_ETAT)
                $valEtatCalc = self::VAL_MIN_ETAT;
            elseif ($valEtatCalc > self::VAL_MAX_ETAT)
                $valEtatCalc = self::VAL_MAX_ETAT;
            $newEtat = $etatCalc;
            foreach (self::$ETAT_CALC_ORDONNE as $etat => $val) {
                if ($val == $valEtatCalc) {
                    $newEtat = $etat;
                    break;
                }
            }
            return $newEtat;
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getNomComplet()
    {
        $nomComplet = $this->prenom;
        if (!empty($this->autreNom))
            $nomComplet .= ' ' . $this->autreNom;
        return $nomComplet . ' ' . $this->nom;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $str = '';
        if (isset($this->autreNom) && $this->autreNom != '')
            $str .= $this->autreNom . ' ';
        $str .= $this->nom . ', ' . $this->prenom;
        if (isset($this->email) && $this->email != '')
            $str = $str . ' (' . $this->email . ')';
        //Pour le moment, on n'affiche pas l'organisme
//     	if (isset($this->organismId) && $this->organismId!='')
//     		$str=$str.', '.$this->organismId;
        if (isset($this->organismId))
            $str = $str . ' - Org: ' . $this->organismId;
        return $str;
    }


    public function calcEtatSelonAffi()
    {
        foreach ($this->HALAuteurAffis as $aff) {
            if ($aff->IsAffiliationConnue() || $aff->IsAffiliationCalculee()) {
                return;
            }
        }
        //s'il n'y a aucune affi calculée ou connue, on remet l'auteur dans l'état affi non calculées
        $this->setEtat(self::MY_ENUM_ETAT_NON_CALC);

    }


    /**
     * calcule les affiliations de un auteur dans le fichier courant
     * qui n'est pas encore affiliés
     * @param Hal_Search_Solr_Api_Affiliation_Author $aut auteur dont on recherche les affiliations
     * @param $annee année de l'article dont on cherche les affiliations de l'auteur
     * @param $params
     * @return bool true si on a persister quelquechose
     */
    public function calcAffiAuteur(Hal_Search_Solr_Api_Affiliation_Author $aut, $annee, $params)
    {
        //recherche dans HAL des affiliations plausibles pour cet auteur
        $res = $this->rechAffiPlusProbable($aut, $annee, $params);


        if ($res != NULL) {
            $aut->setAuteurAvecAffiPlusProbable($res);
            //ajout des affiliations

            foreach ($res['knownlabids'] as $affi) {
                $HALstruct = Hal_Search_Solr_Api_Affiliation_Structure::getLaboComplet($affi);
                $HALAuteurAffi = new Hal_Search_Solr_Api_Affiliation_Structure();
                $HALAuteurAffi->init($HALstruct);
                $aut->addHALAuteurAffis($HALAuteurAffi);
            }
            return true;
        }

        return false;
    }


    /**
     *  mise à jour d'un auteur à partir d'une recherche d'affiliation la plus probable
     * Cas où on n'avait que des infos sur l'auteur et pas d'affiliation
     * @param $res array avec les valeurs
     */
    public function setAuteurAvecAffiPlusProbable($res)
    {
        // on ne remplit les champs auteur que si idHal non renseigné
        if (empty($this->idHal)) {
            if (isset($res['email']) && $res['email'] != '') {
                $this->setEmail($res['email']);
            }
            if (isset($res['emailDomain']) && $res['emailDomain'] != '') {
                $this->setEmailDomain($res['emailDomain']);
            }
            if (isset($res['nom']) && $res['nom'] != '') {
                $this->setNom($res['nom']);
            }
            if (isset($res['prenom']) && $res['prenom'] != '') {
                $this->setPrenom($res['prenom']);
            }
            if (isset($res['url']) && $res['url'] != '') {
                $this->setUrl($res['url']);
            }
            if (isset($res['organismId']) && $res['organismId'] != '') {
                $this->setOrganismId($res['organismId']);
            }
            if (isset($res['idHal']) && $res['idHal'] != '') {
                $this->setIdHal($res['idHal']);
            }
            if (isset($res['docid']) && $res['docid'] != '') {
                $this->setDocid($res['docid']);
            }
        }
        $difAnnee = $res['absDifAnnee'];//Difference d'annee en valeur absolue
        //l'etat de l'affiliation calculée depend du nombre d'annee de
        //différence avec l'annee de publi courante
        if ($difAnnee == 0)//meme annee
        {
            $this->setEtat(self::MY_ENUM_ETAT_CALC_TRES_PROBABLE);
        } else if ($difAnnee > 0 && $difAnnee <= 4) {
            $this->setEtat(self::MY_ENUM_ETAT_CALC_PROBABLE);
        } else if ($difAnnee > 4) {
            $this->setEtat(self::MY_ENUM_ETAT_CALC_POSSIBLE);
        } else //erreur
        {
            $this->setEtat(self::MY_ENUM_ETAT_ERREUR);
        }
    }


    /**
     * Add HALAuteurAffis : ajoute une affiliation HALAuteurAffis
     *
     * @param Hal_Search_Solr_Api_Affiliation_Structure $halAutAffi affiliation
     * @param null $etat_calc AUteurArticle::MY_ENUM_ETAT-CALC_xxx
     * @return bool false si l'ajout n'a pas été effectué car l'affi existe dejà
     */
    public function addHALAuteurAffis(Hal_Search_Solr_Api_Affiliation_Structure $halAutAffi, $etat_calc = null)
    {
        //recherche d'abord si elle n'existe pas déjà sauf pour les nouvelles affiliations (docid=0)
        if ($halAutAffi->getDocid() != 0) {
            foreach ($this->HALAuteurAffis as $HALAuteurAffi) {

                /** @var Hal_Search_Solr_Api_Affiliation_Structure $HALAuteurAffi */

                if ($HALAuteurAffi->getDocid() == $halAutAffi->getDocid()) {
                    return false;//si l'affiliation existe
                }
            }
        }
        $halAutAffi->setIdAuteurArticle($this);


        $this->HALAuteurAffis[] = $halAutAffi;
        $etat = $this->getEtat();
        if (self::compareEtatCalc($etat, $etat_calc))
            $this->setEtat($etat_calc);
        if (!is_null($etat_calc)) {
            //il s'agit d'un etat avec proba de certitude sur l'affiliation
            //on ne met à jour que si la proba est meilleure
            if (stripos($etat_calc, self::MY_ENUM_ETAT_CALC) == 0) {
                if (self::compareEtatCalc($etat, $etat_calc))
                    $this->setEtat($etat_calc);
            } else
                $this->setEtat($etat_calc);
        }
        return true;
    }


    /**
     * @return string
     */
    public function getEtat()
    {
        if (!in_array($this->etat, self::getMyEnumEtatChoices())) {
            return (self::MY_ENUM_ETAT_ERREUR);
        }

        return $this->etat;
    }

    /**
     * @param $etat
     */
    public function setEtat($etat)
    {
        if (!in_array($etat, self::getMyEnumEtatChoices())) {
            throw new \InvalidArgumentException(
                sprintf('Invalid value for AuteurArticle.etat : %s.', $etat)
            );
        }

        $this->etat = $etat;
    }


    /**
     * liste de tous les etats possibles
     * @return array|null
     */
    static public function getMyEnumEtatChoices()
    {
        if (self::$_myEnumFieldValues == null) {
            self::$_myEnumFieldValues = array();
            $oClass = new \ReflectionClass('Hal_Search_Solr_Api_Affiliation_Author');
            $classConstants = $oClass->getConstants();
            $constantPrefix = "MY_ENUM_ETAT_";
            foreach ($classConstants as $key => $val) {
                if (substr($key, 0, strlen($constantPrefix)) === $constantPrefix) {
                    self::$_myEnumFieldValues[$val] = $val;
                }
            }
        }
        return self::$_myEnumFieldValues;
    }


    /**
     * permet de comparer 2 proba de calcul d'affiliation
     * renvoie true si $etatCalcComp > $etatCalc
     * @param $etatCalc
     * @param $etatCalcComp
     * @return bool
     */
    static public function compareEtatCalc($etatCalc, $etatCalcComp)
    {
        if (array_key_exists($etatCalc, self::$ETAT_CALC_ORDONNE) && array_key_exists($etatCalcComp, self::$ETAT_CALC_ORDONNE)) {
            $valEtatCalc = self::$ETAT_CALC_ORDONNE [$etatCalc];
            $valEtatCalcComp = self::$ETAT_CALC_ORDONNE [$etatCalcComp];
            if ($valEtatCalcComp > $valEtatCalc)
                return true;
        }
        return false;
    }

    /**
     * Get autreNom
     *
     * @return string
     */
    public function getAutreNom()
    {
        return $this->autreNom;
    }

    /**
     * Set autreNom
     *
     * @param string $autreNom
     */
    public function setAutreNom($autreNom = null)
    {
        $this->autreNom = $autreNom;
    }

    public function getOrganismId()
    {
        return $this->organismId;
    }

    public function setOrganismId($organismId = null)
    {
        $this->organismId = $organismId;
    }

    public function getCorresponding()
    {
        return $this->corresponding;
    }

    public function setCorresponding($corresponding)
    {
        $this->corresponding = $corresponding;
    }

    /**
     * mise à jour d'un auteur à partir d'une recherche d'affiliation la plus probable
     * Cas où on n'avait que des infos sur l'auteur et sur l'affiliation (label, country ...)
     * $res = array avec les valeurs
     * @param $res
     */
    public function setAuteurAvecAffiRenseigne($res)
    {
        // on ne remplit les champs auteur que si idHal non renseigné
        if (empty($this->idHal)) {
            if (isset($res['email']) && $res['email'] != '') {
                $this->setEmail($res['email']);
            }
            if (isset($res['emailDomain']) && $res['emailDomain'] != '') {
                $this->setEmailDomain($res['emailDomain']);
            }
            if (isset($res['nom']) && $res['nom'] != '') {
                $this->setNom($res['nom']);
            }
            if (isset($res['prenom']) && $res['prenom'] != '') {
                $this->setPrenom($res['prenom']);
            }
            if (isset($res['url']) && $res['url'] != '') {
                $this->setUrl($res['url']);
            }
            if (isset($res['organismId']) && $res['organismId'] != '') {
                $this->setOrganismId($res['organismId']);
            }
            if (isset($res['idHal']) && $res['idHal'] != '') {
                $this->setIdHal($res['idHal']);
            }
            if (isset($res['docid']) && $res['docid'] != '') {
                $this->setDocid($res['docid']);
            }
        }

        if (isset($res['etat_calc'])) {
            $this->setEtat($res['etat_calc']);
        } else //erreur
        {
            $this->setEtat(self::MY_ENUM_ETAT_CALC_POSSIBLE);
        }
    }

    /**
     * valide les affiliations de l'auteur qui sont probables
     */
    public function validAffiliations()
    {
        //si etat calculé --> validé
        if ($this->etat == self::MY_ENUM_ETAT_CALC_POSSIBLE
            || $this->etat == self::MY_ENUM_ETAT_CALC_PROBABLE
            || $this->etat == self::MY_ENUM_ETAT_CALC_TRES_PROBABLE
            || $this->etat == self::MY_ENUM_ETAT_NON_TROUVE_AVEC_AFFI
            || $this->etat == self::MY_ENUM_ETAT_CALC_CERTAIN
        ) {
            $this->etat = self::MY_ENUM_ETAT_VALID;
        }
    }

    public function getInfoReferentielAuthor()
    {
        $info = array('nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'emailDomain' => $this->emailDomain,
            'idHal' => $this->idHal,
            'docid' => $this->docid);
        return $info;
    }


    /**
     * rensoie true si les affiliations doivent être générées cad
     * --soit elles n'ont pas été validées
     * --soit elles ont été validées mais il n'y en a aucune qui a été trouvé
     * @return bool
     */
    public function AffiNonValides()
    {
        $etat = $this->etat;
        if ($etat == self::MY_ENUM_ETAT_ERREUR or
            $etat == self::MY_ENUM_ETAT_NON_VALID or
            $etat == self::MY_ENUM_ETAT_AFF_VIDE or
            $etat == self::MY_ENUM_ETAT_NON_CALC
        ) {
            return true;
        }
        return false;
    }


    /**
     * Object to Array
     * @return array
     */
    public function toArray()
    {
        $author['docid'] = $this->getDocid();
        $author['lastname'] = $this->getNom();
        $author['firstname'] = $this->getPrenom();
        $author['email'] = $this->getEmail();
        $author['emailDomain'] = $this->getEmailDomain();
        $author['url'] = $this->getUrl();
        $author['idHal'] = $this->getIdHal();
        $author['score'] = $this->getEtat();

        return array_filter($author);
    }


    /**
     * créé un auteur à partir de paramètres en entrée de l'API
     * @param array $params
     * @return Hal_Search_Solr_Api_Affiliation_Author
     */
    public static function addAuthorFromParams(array $params) {
        $author = new self();

        if ($params['authId_i'] != '') {
            $author->setDocid($params['authId_i']);
        }

        $author->setPrenom($params['firstName_t']);
        $author->setAutreNom($params['middleName_t']);
        $author->setNom($params['lastName_t']);
        $author->setEmail($params['email_s']);
        return $author;
    }

    /**
     * créé une structure à partir de paramètres en entrée de l'API
     * @param array $structArray
     * @return array|Hal_Search_Solr_Api_Affiliation_Structure
     */
    public function addStructureFromParams(array $structArray) {


        if ((!array_key_exists('structName_t', $structArray)) && (!array_key_exists('structId_i', $structArray))) {
            // struct init useless without it's name or id
           return array();
        }

        $structure = new Hal_Search_Solr_Api_Affiliation_Structure();


        $structure->setName($structArray['structName_t']);
        $structure->setAddrLine($structArray['structAddress_t']);
        $structure->setType($structArray['structType_s']);
        $structure->setAcronym($structArray['structAcronym_s']);
        $structure->setCountry($structArray['structCountry_s']);
        $structure->setDocid($structArray['structId_i']);
        $this->addHALAuteurAffis($structure);
        return $structure;
    }



    public function getDocid()
    {
        return $this->docid;
    }

    public function setDocid($docid = null)
    {
        $this->docid = (int)$docid;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set nom
     *
     * @param string $nom
     */
    public function setNom($nom = null)
    {
        $this->nom = $nom;
    }


    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     */
    public function setPrenom($prenom = null)
    {
        $this->prenom = $prenom;
    }

    public function getEmail()
    {
        return $this->email;
    }


    public function setEmail($email = null)
    {
        $this->email = $email;
    }


    public function getEmailDomain()
    {
        return $this->emailDomain;
    }

    public function setEmailDomain($emailDomain = null)
    {
        $this->emailDomain = $emailDomain;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getIdHal()
    {
        return $this->idHal;
    }

    public function setIdHal($idHal = null)
    {
        $this->idHal = $idHal;
    }

    /**
     * Get  HALAuteurAffis
     *
     * @return array
     */
    public function getHALAuteurAffis()
    {
        return $this->HALAuteurAffis;
    }


}