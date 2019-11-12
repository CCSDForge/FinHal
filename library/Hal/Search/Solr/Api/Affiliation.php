<?php
/**
 * Class Hal_Search_Solr_Api_Affiliation
 * API Affiliation d'après le code de Laurence Farhi
 * Calcul des affiliations
 */

class Hal_Search_Solr_Api_Affiliation
{
    /**
     * Nom du champ solr qui contient la TEI
     */
    const SOLR_TEI_FIELD = 'label_xml';

    /**
     * renvoie un libellé propre pour Solr
     * on enlève les caractère génant
     * @param string $str
     * @return string
     */
    static public function cleanStringSolr($str)
    {
        if (empty($str)) {
            return '';
        }

        $str_ss_ponct = str_replace(['?', ',', '.', ':', ';', '!', '-', '/'], ' ', $str);
        return preg_replace('/\s{2,}/', ' ', $str_ss_ponct);
    }

    /**
     * calcule les affiliations de un auteur selon le cas
     * - pas d'affialation du tout
     * - une affiliation en partie
     * $aut : auteur dont on recherche les affiliations
     * $année : année de l'article dont on cherche les affiliations de l'auteur
     * return : true si on doit persister qqchose
     * @param Hal_Search_Solr_Api_Affiliation_Author $aut
     * @param array $params
     * @return bool
     */
    public function completeAffiliations(Hal_Search_Solr_Api_Affiliation_Author $aut, $params)
    {

        //on verifie d'abord que les affis de cet auteurs ne sont pas valides
        if ($aut->AffiNonValides() === false) {
            return false;
        }


        $annee = $params['producedDate_s'];

        $modif = false;


        $HALAuteurAffis = $aut->getHALAuteurAffis();


        //aucune affiliation, on recherche à partir du nom/prénom/année
        if (empty($HALAuteurAffis) || count($HALAuteurAffis) == 0) {
            return $aut->calcAffiAuteur($aut, $annee, $params);
        }


        // on a des infos sur l'affiliation

        $premAffiTrouve = false;


        /** @var Hal_Search_Solr_Api_Affiliation_Structure $affi */


        foreach ($HALAuteurAffis as $affi) {
            //si on n'a pas déjà calculée cette affiliation
            if ($affi->IsAffiliationCalculee() == false) {
                if ($affi->IsAffiliationConnue() == false)//l'affiliation n'a pas déjà été calculée
                {
                    if ($this->calcAffi($aut, $annee, $affi, $premAffiTrouve) != null)//affiliation trouvée
                    {
                        //on ne renseigne les données sur l'auteur que pour la première affiliation
                        if ($premAffiTrouve == false) {
                            $premAffiTrouve = true;
                        }

                        $affi->setValid(Hal_Search_Solr_Api_Affiliation_Structure::MY_ENUM_VALID_ID_TROUVE);


                        $modif = true;
                    }
                } else {
                    //on a trouvé une affiliation connue
                    if ($aut->getEtat() != Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_VALID &&
                        $affi->getValid() == true
                    ) {
                        $aut->setEtat(Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_VALID);


                        $modif = true;

                    }
                }
            } //si on a trouvé la structure mais pas encore l'auteur
            else if ($affi->IsAffiliationConnue() == true
                && $aut->getEtat() != Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_VALID
                && $premAffiTrouve == false
            ) {
                //il faut chercher l'auteur
                $res = $this->rechUneAffiPlusProbable($aut, $annee, $affi);
                if (!is_null($res)) {
                    $aut->setAuteurAvecAffiRenseigne($res);
                    $premAffiTrouve = true;
                    $modif = true;
                }
            }
        }
        return $modif;

    }

    /**
     * calcule une affiliation de un auteur dans le fichier courant
     * dans le cas où il l'affiliation est décrite en partie
     * @param Hal_Search_Solr_Api_Affiliation_Author $aut auteur que l'on cherche
     * @param int $annee année de l'article
     * @param Hal_Search_Solr_Api_Affiliation_Structure $affiAut affiliation que l'on cherche
     * @param bool $premAffiTrouve si false ==> il faut mettre à jour l'auteur
     * @return bool true si on a trouvé l'affiliation
     */
    public function calcAffi(Hal_Search_Solr_Api_Affiliation_Author $aut, $annee, Hal_Search_Solr_Api_Affiliation_Structure $affiAut, $premAffiTrouve = false)
    {
        $res = $this->rechUneAffiPlusProbable($aut, $annee, $affiAut);

        //on a trouvé l'auteur avec une affiliation qui matche
        if (!is_null($res)) {

            //on ne renseigne les données sur l'auteur que pour la première affiliation
            if ($premAffiTrouve == false) {
                $aut->setAuteurAvecAffiRenseigne($res);
            }
            //ajout des affiliations
            foreach ($res['knownlabids'] as $affi) {
                $HALstruct = Hal_Search_Solr_Api_Affiliation_Structure::getLaboComplet($affi);
                $HALAuteurAffi = new Hal_Search_Solr_Api_Affiliation_Structure();
                $HALAuteurAffi->init($HALstruct);
                $aut->addHALAuteurAffis($HALAuteurAffi);
            }

            return true;
        } else {
            /*
             * sinon on recherche l'affiliation seule
             */
            $HALstruct = new Hal_Search_Solr_Api_Affiliation_Structure();
            $HALstruct->copy($affiAut);
            $HALstructRes = $HALstruct->findByHALStructure($HALstruct);
            //on a trouvé au moins une structure qui matche
            //on prend la première car elles sont classées par valid (les VALID d'abord)
            if (!is_null($HALstructRes)) {
                if ($premAffiTrouve == false) {
                    $aut->setEtat(Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_NON_TROUVE_AVEC_AFFI);
                }

                $HALAuteurAffi = new Hal_Search_Solr_Api_Affiliation_Structure();
                $HALAuteurAffi->init($HALstructRes);
                $aut->addHALAuteurAffis($HALAuteurAffi);


                return true;
            }
        }
        return false;
    }

    /**
     * Cherche dans HAL les affiliations d'un auteur étant donné une année + nom, prenom, email
     * en trouvant celui qui s'en rapproche le plus
     * et au plus proche d'une année donnée
     * @param Hal_Search_Solr_Api_Affiliation_Author $auteur Auteur à rechercher
     * @param string $annee annee du depot dont on cherche l'affiliation
     * @param Hal_Search_Solr_Api_Affiliation_Structure $HALAuteurAffi si on l'a en partie affiliation (HALLabel_s ou HALTutelles)
     * @return
     */
    public function rechUneAffiPlusProbable(Hal_Search_Solr_Api_Affiliation_Author $auteur, $annee, Hal_Search_Solr_Api_Affiliation_Structure $HALAuteurAffi)
    {


        $nom = $auteur->getNom();
        $prenom = $auteur->getPrenom();

        //on supprime accents et majuscules
        $nomFormatte = self::drop_accent_and_lower($nom);
        $prenomFormatte = self::drop_accent_and_lower($prenom);
        $listeDoc = null;
        $listSimilarLabels = null;

        $q = [];
        $q['sort'] = '';

        if (!is_null($auteur->getIdHal())) {
            $q['q'] = "authIdHal_s:" . $auteur->getIdHal();
        } elseif (!is_null($auteur->getDocid())) {
            $q['q'] = "authId_i:" . $auteur->getDocid();
        } else {
            $q['q'] = "authLastName_sci:\"$nom\" AND authFirstName_sci:\"$prenom\"";
        }


        $q['fl'] = "halId_s,authFullName_s,authLastName_s,authFirstName_s,label_xml";

        if ($annee) {
            $q['fl'] = ",sub(producedDateY_i,$annee),abs(sub(producedDateY_i,$annee))";
            $q['sort'] = "abs(sub(producedDateY_i,$annee)) asc,";
            $q['sort'] .= "sub(producedDateY_i,$annee) asc,";
        }


        $q['sort'] .= "producedDate_tdate desc";//si même année, on prend le plus récent

        //normalement, si on est là, $HALAuteurAffi n'est pas null

        if ($HALAuteurAffi == null) {
            return null;
        }
        $fq = "";
        $valPonder = 0;//ponderation de la proba que l'affi soit bonne
        $res = NULL;
        //si on a l'identifiant HAL de l'affiliation, ça suffit
        $affiHALdocid = $HALAuteurAffi->getDocid();


        // on cherche avec le docid de la structure
        if (!empty($affiHALdocid)) {
            $q['fq'] = "structId_i:$affiHALdocid";
            $q['rows'] = 50;
            $q = array_map('trim', $q);
            $query = http_build_query($q);
            $listeDocSolr = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_Halv3::$_coreName);
            $listeDoc = unserialize($listeDocSolr);

        } else {
            //on a d'autres infos sur l'affiliation que l'id
            //on essaie d'abord de trouver sur les termes exactes
            $affiName = $HALAuteurAffi->getName();

            $affiAcronym = $HALAuteurAffi->getAcronym();
            $name = self::drop_accent_and_lower($affiName);
            $acronym = self::drop_accent_and_lower($affiAcronym);
            $listSimilarLabels = [];
            if (!is_null($name)) {
                $listSimilarLabels[] = $name;
            }
            if (!is_null($acronym)) {
                $listSimilarLabels[] = $acronym;
            }

            if (!is_null($acronym) && $acronym != '' && !is_null($name) && $name != '') {
                $fq = self::addFqAnd($fq, " structAcronym_sci:\"$acronym\" AND structName_sci:\"$name\"");
            } elseif (!is_null($name) && $name != '') {
                $fq = self::addFqAnd($fq, " (structAcronym_sci:\"$name\" OR structName_sci:\"$name\")");
            } elseif (!is_null($acronym) && $acronym != '') {
                $fq = self::addFqAnd($fq, " (structAcronym_sci:\"$acronym\" OR structName_sci:\"$acronym\")");
            }
            //ATTENTION, POUR LE MOMENT PAS DE TEST SUR EMAIL car pas dans l'API (mantis)
            //ATTENTION traiter middleName

            $q['fq'] = $fq;


            $q = array_map('trim', $q);
            $query = http_build_query($q);


            $listeDocSolr = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_Halv3::$_coreName);
            $listeDoc = unserialize($listeDocSolr);


            $numFound = (int)$listeDoc['response']['numFound'];

            //si on n'en a pas trouve, on cherche plus large
            if ($numFound == 0) {
                $listSimilarLabels = $HALAuteurAffi->getAllSimilarLabels();
                $arrFq = [];
                foreach ($listSimilarLabels as $lib) {
                    $arrFq[] = "(structAcronym_t:$lib) OR (structName_t:$lib)";
                }
                $fq = implode(" OR ", $arrFq);

                $q['fq'] = $fq;
                $q = array_map('trim', $q);
                $query = http_build_query($q);
                $listeDocSolr = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_Halv3::$_coreName);
                $listeDoc = unserialize($listeDocSolr);

                if (!empty($listeDoc)) {
                    $valPonder = -1;//ponderation de la proba que l'affi soit bonne
                }
            }
        }


        // rien trouvé

        if (empty($listeDoc)) {
            return null;
        }

        /*
           * on recherche parmi les doc trouvé le 1er qui a des affiliations
           */
        foreach ($listeDoc['response']['docs'] as $doc) {

            if (!array_key_exists(self::SOLR_TEI_FIELD, $doc)) {
                continue; // no XML : meh
            }

            $tei = $doc[self::SOLR_TEI_FIELD];


            $xml = $this->loadFromTEIText($tei);
            $xpath = $this->createXpathTEI($xml);
            $reqXpath = '/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:analytic/tei:author/tei:persName';
            $persNames = $xpath->query($reqXpath);


            if (!$persNames->length > 0) {
                continue;
            }


            $prenomStr = null;
            $nomStr = null;
            //on parcourt les affiliations trouvées
            /** @var DOMElement $persNameElt */
            foreach ($persNames as $persNameElt) {
                //prenom
                $prenoms = $persNameElt->getElementsByTagName('forename');
                if ($prenoms->length >= 1) {
                    /** @var DOMElement $prenom */
                    foreach ($prenoms as $prenom) {
                        if ($prenom->getAttribute('type') == 'first') {
                            $prenomStr = $prenom->nodeValue;
                            break;
                        }
                    }
                }
                //nom
                $noms = $persNameElt->getElementsByTagName('surname');
                if ($noms->length > 0) {
                    $nom = $noms->item(0)->nodeValue;
                    if (!empty($nom)) {
                        $nomStr = $nom;
                    }
                }
                //est-ce le bon auteur ? attention, sans les accents ni casse
                if (!is_null($nomStr) && !is_null($prenomStr) &&
                    self::drop_accent_and_lower($nomStr) == $nomFormatte &&
                    self::drop_accent_and_lower($prenomStr) == $prenomFormatte
                ) {
                    //c'est le bon auteur, a-t'il des affiliations
                    $authorElt = $persNameElt->parentNode;
                    $struct = null;
                    $affiliations = $authorElt->getElementsByTagName('affiliation');


                    if ($affiliations->length > 0) {
                        /*
                           * on a trouvé cet auteur avec au moins une affiliation
                          */
                        $structOK = false;
                        $refStruct = '';
                        /** @var DOMElement $affiElt */
                        foreach ($affiliations as $affiElt) {
                            $refStruct = $affiElt->getAttribute('ref');
                            if (!empty($refStruct)) {
                                // Recherche du noeud qui décrit la structure dans le back
                                $orgNode = $this->getAffiNodeByRef($xpath, $refStruct);
                                // soit on avait au départ l'idHAL de la structure

                                if (!empty($affiHALdocid)) {
                                    $idStruct = self::calcStructIdFromElement($refStruct);
                                    if ($idStruct == $affiHALdocid) {
                                        $structOK = Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_CALC_CERTAIN;
                                        break;
                                    }
                                } else {

                                    // Recup info structure
                                    $acronym = $this->getAcronymeInOrgNode($orgNode, $xpath);
                                    $name = $this->getNameInOrgNode($orgNode, $xpath);

                                    if (self::compareStructByArrayLabels($acronym, $listSimilarLabels) || self::compareStructByArrayLabels($name, $listSimilarLabels)) {
                                        //structure trouvée
                                        $structOK = Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_CALC_CERTAIN;
                                        break;
                                    }

                                    if ($structOK != false) {
                                        break;
                                    }
                                }
                            }
                        }


                        //On peut aller chercher les autres infos nécessaires
                        if ($structOK != false) {
                            //idHalauteur
                            $ptrs = $authorElt->getElementsByTagName('idno');
                            $idHalCourant = null;
                            $idHalauthorCourant = null;
                            if ($ptrs->length > 0) {
                                /** @var DOMElement $ptr */
                                foreach ($ptrs as $ptr) {
                                    $notation = $ptr->getAttribute('notation');
                                    $typeIdno = strtolower($ptr->getAttribute('type'));
                                    if (($typeIdno == 'idhal') && $notation == 'string') {
                                        $idHalCourant = $ptr->nodeValue;
                                    } elseif ($typeIdno == 'halauthorid') {
                                        $idHalauthorCourant = $ptr->nodeValue;
                                    }
                                }
                            }
                            //recherche d'autres infos
                            $res['prenom'] = $prenomStr;
                            $res['nom'] = $nomStr;
                            $res['idHal'] = $idHalCourant;
                            $res['docid'] = $idHalauthorCourant;

                            //email
                            /* on ne recherche plus l'mail car il est en MD5
                            $emails=$authorElt->getElementsByTagName('email');
                              if ($emails->length > 0) {
                                  $email=$emails->item(0)->nodeValue;
                                  if(!empty($email))
                                    $res['email']=$email;
                            }*/
                            //organism
                            $orgNames = $authorElt->getElementsByTagName('orgName');
                            if ($orgNames->length > 0) {
                                $orgRef = $orgNames->item(0)->getAttribute('ref');
                                $idOrg = self::calcStructIdFromElement($orgRef);
                                if (!empty($idOrg)) {
                                    $res['organismId'] = $idOrg;
                                }
                            }
                            //url
                            $ptrs = $authorElt->getElementsByTagName('ptr');
                            if ($ptrs->length >= 1) {
                                foreach ($ptrs as $ptr) {
                                    if ($ptr->getAttribute('type') == 'url') {
                                        $res['url'] = $ptr->getAttribute('target');
                                        break;
                                    }
                                }
                            }
                            //on va chercher les affiliations si on n'avait pas au départ l'id de l'affiliation
                            if (empty($affiHALdocid)) {
                                $structArray = [];
                                $idStruct = self::calcStructIdFromElement($refStruct);
                                $structArray[] = $idStruct;
                                $res['knownlabids'] = $structArray;
                                $etatCalcNew = Hal_Search_Solr_Api_Affiliation_Author::calcEtatCalc($structOK, $valPonder);
                                $res['etat_calc'] = $etatCalcNew;
                            } else {
                                $res['etat_calc'] = $structOK;
                            }
                            unset($xpath);
                            unset($xml);
                            unset($listeDocSolr);
                            unset($listeDoc);
                            return $res;
                        }
                    }

                }

            }

            unset($xpath);
            unset($xml);
        }
        unset($listeDocSolr);
        unset($listeDoc);
        return $res;
    }

    /**
     * supprime tous les accent + minuscule
     * @param $str
     * @param string $encoding
     * @return mixed|string
     */
    public static function drop_accent_and_lower($str, $encoding = 'utf-8')
    {
        if (is_null($str)) {
            return $str;
        }
        // transformer les caractères accentués en entités HTML
        $str = htmlentities($str, ENT_NOQUOTES, $encoding);

        // remplacer les entités HTML pour avoir juste le premier caractères non accentués
        // Exemple : "&ecute;" => "e", "&Ecute;" => "E", "Ã " => "a" ...
        $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);

        // Remplacer les ligatures tel que : Œ, Æ ...
        // Exemple "Å“" => "oe"
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        // Supprimer tout le reste
        $str = preg_replace('#&[^;]+;#', '', $str);
        $str = str_replace(['æ', 'Æ', 'œ', 'Œ', 'ý', 'ÿ', 'Ý', 'ç', 'Ç', 'ñ', 'Ñ'], ['ae', 'AE', 'oe', 'OE', 'y', 'y', 'Y', 'c', 'C', 'n', 'N'], $str);
        $str = strtolower($str);

        return $str;
    }

    /**
     * @param $fq
     * @param $add
     * @return string
     */
    static public function addFqAnd($fq, $add)
    {
        if (!empty($fq)) {
            $fqRes = $fq . ' AND ' . $add;
        } else {
            $fqRes = $add;
        }

        return $fqRes;
    }

    /**
     * Créer un DOMDocument à partir de la TEI sous forme d'une chaine de caractère
     * @param $tei
     * @return DOMDocument|null
     */
    public function loadFromTEIText($tei)
    {


        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        //seul moyen pour avoir de l'utf8 dans le xml
        $tei_utf8 = '<?xml version="1.0" encoding="UTF-8"?>' . $tei;


        if (!$xml->loadXML($tei_utf8)) {
            return null;
        }


        return $xml;
    }

    /**
     * création d'un xpath pour faire des requetes compatibles avec la TEI
     * @param $xmlTei DOMDocument au format TEI
     * @return DOMXPath|null
     */
    public function createXpathTEI($xmlTei)
    {

        if ($xmlTei == null) {
            return null;
        }
        $xpath = new \DOMXPath($xmlTei);
        $xpath->registerNamespace('', "http://www.tei-c.org/ns/1.0");
        $xpath->registerNamespace('tei', "http://www.tei-c.org/ns/1.0");
        $xpath->registerNamespace('hal', "http://hal.archives-ouvertes.fr/");
        $xpath->registerNamespace('xml', "http://www.w3.org/XML/1998/namespace");
        return $xpath;

    }

    /**
     * calcule une affiliation étant donnée la TEI ($xpath) et l'id d'une structure ($refStruct)
     * ==> recherche de <back> de cette affiliation
     * retour : Node xml de la structure dans le <back>
     * @param DOMXPath $xpath
     * @param string $refStruct : id d'une structure
     * @return null
     */
    public function getAffiNodeByRef($xpath, $refStruct)
    {
        if (!is_null($xpath) && !empty($refStruct)) {
            $idStruct = str_replace("#", "", $refStruct);
            $orgs = $xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg/tei:org[@xml:id="' . $idStruct . '"]');
            $orgNode = null;
            if ($orgs->length > 0) {
                $orgNode = $orgs->item(0);
            }
            return $orgNode;
        }
        return null;
    }

    /**
     * calcule un identifiant de structure à partir de la valeur d'un élément orgName ou affiliation
     * dans le xml
     * $structValue = "#struct-xxx" où xxx est l'id de la structure
     * @param $structValue
     * @return int|null
     */
    public static function calcStructIdFromElement($structValue)
    {
        $struct = str_replace('#struct-', '', $structValue);
        if (!empty($struct) && is_numeric($struct)) {
            $idStruct = intval($struct);
            return $idStruct;
        }
        return null;
    }

    /**
     * A partir du xml d'une structure $orgNode du back + $xpath
     * retourne l'acronyme
     * @param $orgNode
     * @param DOMXPath $xpath
     * @return string
     */
    public function getAcronymeInOrgNode($orgNode, $xpath)
    {
        $acronyme = null;
        $acronymes_org = $xpath->query('./tei:orgName[@type="acronym"]', $orgNode);
        if ($acronymes_org->length > 0) {
            $acronyme = $acronymes_org->item(0)->nodeValue;
        }
        return $acronyme;
    }

    /**
     * A partir du xml d'une structure $orgNode du back + $xpath
     * retourne le nom de la structure
     * @param $orgNode
     * @param DOMXPath $xpath
     * @return string
     */
    public function getNameInOrgNode($orgNode, $xpath)
    {
        $name = null;
        $names = $xpath->query('./tei:orgName[not(@type)]', $orgNode);
        if ($names->length > 0) {
            $name = $names->item(0)->nodeValue;
        }
        return $name;
    }

    /**
     * Vérifie qu'une structure trouvé dans la TEI $labelTEI est similaire à une des structures possibles
     * $listSimilarLabels : liste de libelles à chercher
     * $labelTEI : libelle trouvé dans la TEI (acronyme ou name)
     * retourne true s'il s'agit d'une structure similaire
     * false sinon
     * @param $labelTEI
     * @param $listSimilarLabels
     * @return bool
     */
    public static function compareStructByArrayLabels($labelTEI, $listSimilarLabels)
    {
        if (empty($labelTEI))
            return false;
        $labelTEIClean = self::drop_accent_and_lower($labelTEI);
        //on regarde d'abord si on trouve la valeur exacte
        if (in_array($labelTEIClean, $listSimilarLabels)) {
            return true;
        }
        //on teste ensuite la ressemblance et non sur l'égalité
        foreach ($listSimilarLabels as $similarLabel) {
            $similarLabelClean = self::drop_accent_and_lower($similarLabel);
            $pattern = "/" . preg_replace('/[^a-z0-9]+/', '.*', $similarLabelClean) . "/";
            $isValid = preg_match($pattern, $labelTEIClean);

            if ($isValid) {
                return true;
            }

        }
        return false;
    }

    /**
     * Cherche dans HAL les affiliations d'un auteur étant donné une année + nom, prenom, email
     * en trouvant celui qui s'en rapproche le plus
     * et au plus proche d'une année donnée
     * retour : $res=array('knownlabids','prenom','nom','email','organismId','url','idHal','idHalauthor''absDifAnnee')
     * @param Hal_Search_Solr_Api_Affiliation_Author $auteur Auteur à rechercher
     * @param int $annee annee du depot dont on cherche l'affiliation
     * @param $params
     * @return mixed|null
     */
    public function rechAffiPlusProbable(Hal_Search_Solr_Api_Affiliation_Author $auteur, $annee, $params)
    {
        $nom = $auteur->getNom();
        $prenom = $auteur->getPrenom();
        $email = strtolower($auteur->getEmail());
        $annee = $params['producedDate_s'];


        $q['q'] = "status_i:11";
        $q['fl'] = "halId_s,authFullName_s,authLastName_s,authFirstName_s,label_xml";

        if ($annee) {
            $q['fl'] .= ",sub(producedDateY_i,$annee),abs(sub(producedDateY_i,$annee))";
        }

        if (!is_null($auteur->getIdHal())) {
            $q['fq'] = "authIdHal_s:" . $auteur->getIdHal();
        } elseif (!is_null($auteur->getDocid())) {
            $q['fq'] = "authId_i:" . $auteur->getDocid();
        } else {
            $q['fq'] = "authLastName_sci:\"$nom\" AND authFirstName_sci:\"$prenom\"";

        }

        if ($annee) {
            $q['sort'] = "abs(sub(producedDateY_i,$annee)) asc,sub(producedDateY_i,$annee) asc,producedDate_tdate desc"; //si même année, on prend le plus récent
        }


        $q = array_map('trim', $q);

        $query = http_build_query($q);


        $listeDocSolr = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_Halv3::$_coreName);


        $listeDocSolrArr = unserialize($listeDocSolr);


        if ((!array_key_exists('response', $listeDocSolrArr)) || ($listeDocSolrArr['response']['numFound'] <= 0)) {
            return null;
        }


        $res = null;
        //on a trouvé des documents

        $listeDoc = $listeDocSolrArr['response']['docs'];
        $resTrouve = null;
        /*
           * on recherche parmi les documents trouvés le 1er qui a des affiliations
           */
        foreach ($listeDoc as $doc) {
            $tei = $doc[self::SOLR_TEI_FIELD];
            $xml = $this->loadFromTEIText($tei);
            $xpath = $this->createXpathTEI($xml);
            $res = $this->rechAuteurDsTei($xpath, $auteur, $annee);


            if (!is_null($res))//on a trouve un bon auteur avec une affi
            {
                //si on a une adresse email, on regarde si le domaine correspond
                if (!empty($email)) {
                    //si en plus l'adresse email correspond au domaine
                    if ($res['emailMatch'] == true) {
                        $resTrouve = $res;
                        break;
                    } else {
                        //on le garde de côté
                        if (is_null($resTrouve)) {
                            $resTrouve = $res;
                        }
                    }
                } else {
                    //pas d'adresse email au départ, on s'arrête
                    $resTrouve = $res;
                    break;
                }
            }
        }

        return $resTrouve;

    }


    /*
     *  ----------------------------------------------
     *
     * Ci dessous code pour mémoire, voir ce qui pourrait servir en fusionnant avec le code de Laurence
     *
     *
     *
     **/

    /**
     * Recherche un auteur dans la TEI et renvoie l'élément trouvé
     * retour : $res=array('knownlabids','prenom','nom','email','organismId','url','idHal','idHalauthor''absDifAnnee')
     *
     * @param DOMXPath $xpath xpath de la TEI
     * @param Hal_Search_Solr_Api_Affiliation_Author $auteur
     * @param $annee
     * @return null
     */
    private function rechAuteurDsTei($xpath, Hal_Search_Solr_Api_Affiliation_Author $auteur, $annee)
    {

        $nom = $auteur->getNom();
        $prenom = $auteur->getPrenom();
        $email = $auteur->getEmail();
        //on supprime accents et majuscules
        $nomFormatte = self::drop_accent_and_lower($nom);
        $prenomFormatte = self::drop_accent_and_lower($prenom);
        $idHal = $auteur->getIdHal();
        $idHalauthor = $auteur->getDocid();

        if ($xpath == null) {
            return null;
        }

        $reqXpath = '/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:analytic/tei:author/tei:persName';
        $persNames = $xpath->query($reqXpath);

        //on parcourt les affiliations trouvées
        /** @var DOMElement $persNameElt */
        foreach ($persNames as $persNameElt) {
            $prenomStr = null;
            $nomStr = null;
            $idHalCourant = null;
            $idHalauthorCourant = null;
            //prenom
            $prenoms = $persNameElt->getElementsByTagName('forename');
            if ($prenoms->length >= 1) {
                /** @var DOMElement $prenom */
                foreach ($prenoms as $prenom) {
                    if ($prenom->getAttribute('type') == 'first') {
                        $prenomStr = $prenom->nodeValue;
                        break;
                    }
                }
            }
            //nom
            $noms = $persNameElt->getElementsByTagName('surname');
            if ($noms->length > 0) {
                $nom = $noms->item(0)->nodeValue;
                if (!empty($nom)) {
                    $nomStr = $nom;
                }
            }
            $authorElt = $persNameElt->parentNode;
            //idHalauteur
            $ptrs = $authorElt->getElementsByTagName('idno');
            if ($ptrs->length > 0) {
                /** @var DOMElement $ptr */
                foreach ($ptrs as $ptr) {
                    //pb nom de l'attribut modifie
                    //if($ptr->getAttribute('type')=='idHal')
                    $notation = $ptr->getAttribute('notation');
                    $typeIdno = strtolower($ptr->getAttribute('type'));
                    if (($typeIdno == 'idhal') && $notation == 'string') {
                        $idHalCourant = $ptr->nodeValue;
                    } elseif ($typeIdno == 'halauthorid') {
                        $idHalauthorCourant = $ptr->nodeValue;
                    }
                }
            }
            //est-ce le bon auteur ? attention, sans les accents ni casse
            if ((!is_null($nomStr) && !is_null($prenomStr) && $this->drop_accent_and_lower($nomStr) == $nomFormatte && $this->drop_accent_and_lower($prenomStr) == $prenomFormatte)
                || (!is_null($idHal) && $idHal == $idHalCourant)
                || (!is_null($idHalauthor) && $idHalauthor == $idHalauthorCourant)
            ) {
                //c'est le bon auteur, a-t'il des affiliations
                $arrIdStruct = [];
                $affiliations = $authorElt->getElementsByTagName('affiliation');
                if ($affiliations->length > 0) {
                    /**
                     * on a trouvé cet auteur avec au moins une affiliation
                     * @var DOMElement $affiElt
                     */
                    foreach ($affiliations as $affiElt) {
                        $refStruct = $affiElt->getAttribute('ref');
                        $idStruct = self::calcStructIdFromElement($refStruct);
                        if (!empty($idStruct)) {
                            $arrIdStruct[] = $idStruct;
                        }
                    }
                    //on a trouvé des affiliations pour cet auteur, on peut aller chercher les autres infos nécessaire
                    if (!empty($arrIdStruct)) {
                        $res['knownlabids'] = $arrIdStruct;
                        //recherche d'autres infos
                        $res['prenom'] = $prenomStr;
                        $res['nom'] = $nomStr;
                        $res['idHal'] = $idHalCourant;
                        $res['docid'] = $idHalauthorCourant;
                        //email
                        /* on ne recherche plus l'mail car il est en MD5
                         *
                         $emails=$authorElt->getElementsByTagName('email');
                          if ($emails->length > 0) {
                              $email=$emails->item(0)->nodeValue;
                              if(!empty($email))
                                $res['email']=$email;
                        }*/
                        //domain de l'email
                        $res['emailMatch'] = false;
                        $emailDomains = $authorElt->getElementsByTagName('email');
                        if ($emailDomains->length > 0) {
                            /** @var DOMElement $emailDomain */
                            foreach ($emailDomains as $emailDomain) {
                                if ($emailDomain->getAttribute('type') == 'domain') {
                                    $res['emailDomain'] = strtolower($emailDomain->nodeValue);
                                    //si on a un domaine et une adresse email, on les compare
                                    if ((!empty($email)) && (strpos($email, $res['emailDomain']) !== false)) {
                                        $res['emailMatch'] = true;
                                    }
                                    break;
                                }
                            }
                        }
                        //si on a l'adresse email dans la source, on regarde si on le meme domaine				  			}
                        //organism
                        $orgNames = $authorElt->getElementsByTagName('orgName');
                        if ($orgNames->length > 0) {
                            $orgRef = $orgNames->item(0)->getAttribute('ref');
                            $idOrg = self::calcStructIdFromElement($orgRef);
                            if (!empty($idOrg)) {
                                $res['organismId'] = $idOrg;
                            }
                        }
                        //url
                        $ptrs = $authorElt->getElementsByTagName('ptr');
                        if ($ptrs->length >= 1) {
                            foreach ($ptrs as $ptr) {
                                if ($ptr->getAttribute('type') == 'url') {
                                    $res['url'] = $ptr->getAttribute('target');
                                    break;
                                }
                            }
                        }
                        /*calcul de la difference entre l'année de la publi trouvée
                         * et l'année du dépôts
                         */
                        $reqXpath = '/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:editionStmt/tei:edition/tei:date[@type="whenProduced"]';
                        $entries = $xpath->query($reqXpath);
                        if ($entries->length == 1) {
                            $producedDate = $entries->item(0)->nodeValue;
                            //normalement, année=4 premiers chiffres
                            if (!empty($producedDate)) {
                                $producedDateYear = substr($producedDate, 0, 4);
                                if (!empty($producedDateYear) && is_numeric($producedDateYear)) {
                                    $year = intval($producedDateYear);
                                    $res['absDifAnnee'] = abs($year - $annee);
                                }
                            }
                        }
                        return $res;
                    }
                }

            }
        }
        return null;
    }

    /**
     * Find structures
     * @repository REF_STRUCTURE
     * @param string $name
     * @param string $address
     * @param string $type
     * @return array|bool
     * @deprecated
     */
    public
    function findStructures($name, $address = null, $type = null)
    {

        $stringToSearch = '';
        $q['qf'] = '';


        if ($name) {
            $stringToSearch .= '(' . $name . ')';
            $q['qf'] = 'name_t^1 acronym_t^1 code_t^1 name_sci^2 acronym_sci^2 code_sci^2'; // which fields to search into, with boost
        }

        if ($address) {
            $stringToSearch .= ' (' . $address . ')';
            $q['qf'] .= ' address_t^0.5';// which fields to search into, with boost
        }

        if ($type) {
            $strucTypes = new Ccsd_Referentiels_Structure();
            $strucTypesArr = $strucTypes->getTypes();
            //NFG if not a valid type struct
            if (in_array($type, $strucTypesArr)) {
                $q['bq'] = 'type_s:' . $type . '^0.1'; // query boost by field value
            }
        }

        $q['q'] = $stringToSearch;


        //$q['bq'] = 'valid_s:VALID^0.2 OR valid_s:INCOMING^0.1'; // query boost by field value
        $q['bq'] = 'valid_s:VALID^0.1'; // query boost by field value
        $q['wt'] = 'json';
        $q['fl'] = 'docid,*_s,score';
        $q['defType'] = 'dismax';
        $q = array_map('trim', $q);

        $query = http_build_query($q);

        $structures = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_RefStructure::$_coreName);

        $structuresArr = json_decode($structures, true);

        if ($structuresArr['response']['numFound'] <= 0) {
            return false;
        }

        return $structuresArr['response']['docs'];


        /*
    "mm": "2",
    "q": "CNRS/MCC",
    "defType": "dismax",
    "indent": "true",
    "qf": "name_t^2 acronym_t^2 code_t^2 parentName_t parentAcronym_t parentCode_t",
    "fl": "docid,*_s",
    "fq": "valid_s:VALID",
    "wt": "json",
    */


    }

    /**
     * Returns Affiliations info from an array of author forms
     * @repository HAL
     * @param array $arrayOfAuthors array of author docids
     * @param string $keywords
     * @param string $date
     * @return false|array array of structure docids + name + score
     * @deprecated
     */
    public
    function findAuthorsAffiliationsInDocuments(array $arrayOfAuthors, $keywords = null, $date = null)
    {

        $authArr = false;

        foreach ($arrayOfAuthors as $authorId) {
            $facetsArr[$authorId]['terms'] = ['field' => 'authIdHasPrimaryStructure_fs', 'prefix' => $authorId . Ccsd_Search_Solr::SOLR_FACET_SEPARATOR];
        }

        header('Content-Type: application/json; charset=utf-8');

        $q['q'] = '*:*';
        $q['wt'] = 'json';
        $q['rows'] = 0;
        $q['json.facet'] = json_encode($facetsArr);


        if ($keywords != null) {
            $q['fq'] = 'keyword_t:(' . str_replace(',', ' OR ', $keywords) . ')';
        }

        if ($date != null) {
            $q['fq'] = 'producedDateY_i:(' . str_replace(',', ' OR ', $date) . ')';
        }


        $query = http_build_query($q);

        $authStructRes = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_Halv3::$_coreName, null, null);

        $authStructArr = json_decode($authStructRes, true);


        if (!isset($authStructArr['facets'])) {
            return false;
        }

        unset($authStructArr['facets']['count']); // NFG

        foreach ($authStructArr['facets'] as $authDocid => $facet) {

            $buckets = $this->extractStructFromFacetBucket($facet['buckets'], $authDocid);

            if ($buckets) {
                $affiliations = $buckets['affiliations'];
                $affiliations_score = $buckets['affiliation-score'];
                $authArr[$authDocid][] = [$affiliations, $affiliations_score];
            }
        }

        return $authArr;


    }

    /**
     * Returns an array of structure info extracted from facet buckets
     * @param array $facetsArr
     * @return array array of structure info
     * @deprecated
     */
    private
    function extractStructFromFacetBucket(array $facetsArr)
    {

        $authorFormScore = 0;
        foreach ($facetsArr as $facet) {

            $AuthAndStruct = explode(Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR, $facet['val']);
            $structArr = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $AuthAndStruct[1]);

            $structId = $structArr[0];
            $structName = $structArr[1];
            $authorFormScore += $facet['count'];
            $arrayOfStructures[] = ['docid' => (int)$structId, 'name' => $structName, 'score' => $facet['count']];
        }

        return ['affiliations' => $arrayOfStructures, 'affiliation-score' => $authorFormScore];

    }

    /**
     * Format affiliations to return one deduplicated list of author forms affiliations
     * @param array $affiliations
     * @return array affiliations from documents sorted by score
     * @deprecated
     */
    public
    function formatAuthorsAffiliationList(array $affiliations)
    {

        $affiliationsList = [];
        foreach ($affiliations as $aff) {
            foreach ($aff[0][0] as $structure) {
                $docid = $structure['docid'];

                if (array_key_exists($docid, $affiliationsList)) {
                    $count = $affiliationsList[$docid]['score'] + $structure['score'];
                } else {
                    $count = $structure['score'];
                }
                $affiliationsList[$docid] = ['docid' => $docid, 'name' => $structure['name'], 'score' => $count];
            }
        }

        $affiliationsList = array_values($affiliationsList);

        // sort by score
        usort($affiliationsList, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $affiliationsList;
    }

    /**
     * Merge Authors and Affiliations
     * @param array $authors
     * @param array $affiliations
     * @return array merged Authors and Affiliations with scores
     * @deprecated
     */
    public
    function mergeAuthorsAffiliations(array $authors, array $affiliations)
    {
        foreach ($authors as $id => $author) {

            $authId = $author['docid'];
            $authors[$id]['affiliations'] = $affiliations[$authId][0];
            $authors[$id]['score-affiliation'] = $affiliations[$authId][0][1];
        }
        return $authors;
    }


//supprime tous les accent + minuscule

    /**
     * Find author Forms in authors repository
     * @param array $params
     * @return array|boolean
     * @deprecated
     */
    public
    function findAuthorForms($params)
    {

        if ($params['fullName_t'] != null) {
            $authorForms = $this->findAuthorFormsByFullName($params['fullName_t'], $params['email_s']);
        }


        if ($params['lastName_t']) {
            $authorForms = $this->findAuthorFormsByNames($params['lastName_t'], $params['firstName_t'], $params['middleName_t'], $params['email_s']);

        }


        if (!is_array($authorForms)) {
            return false;
        }

        return $authorForms;


    }

    /*
    * calcule un identifiant de structure à partir de la valeur d'un élément orgName ou affiliation
    * dans le xml
    * $structValue = "#struct-xxx" où xxx est l'id de la structure
    */

    /**
     * Find authors in author repository according to their fullname
     * @param string $fullname
     * @return array|boolean
     * @deprecated
     * @repository REF_AUTHOR
     */
    private
    function findAuthorFormsByFullName($fullname, $email = null)
    {
        $q['q.alt'] = '(' . $fullname . ')';
        $q['qf'] = 'lastName_t^3 firstName_t^2 middleName_t^0.5';
        $q['pf'] = 'fullName_sci^4';
        $q['bq'] = 'valid_s:VALID^20.2'; // query boost by field value
        $q['wt'] = 'json';
        $q['fl'] = 'docid,*_s,score';
        $q['defType'] = 'dismax';


        if ($email) {
            $q['q.alt'] .= ' ' . $email;
            $q['qf'] .= ' email_s^2';// which fields to search into, with boost
        }

        $query = http_build_query($q);

        $authors = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_RefAuthor::$_coreName);

        $authorsArr = json_decode($authors, true);

        if ($authorsArr['response']['numFound'] <= 0) {
            return false;
        }

        return $authorsArr['response']['docs'];
    }

    /**
     * Find authors in author repository according to their lastname,  firstname, middlename
     * @param string $lastname
     * @param string $firstname
     * @param string $middlename
     * @return array|boolean
     * @deprecated
     * @repository REF_AUTHOR
     */
    private
    function findAuthorFormsByNames($lastname = null, $firstname = null, $middlename = null, $email = null)
    {

        $stringToSearch = '';
        $q['qf'] = '';


        if ($firstname) {
            $stringToSearch .= $firstname;
            $q['qf'] = 'firstName_t^1 firstName_sci^2'; // which fields to search into, with boost
        }

        if ($middlename) {
            $stringToSearch .= ' ' . $middlename;
            $q['qf'] .= 'middleName_t^0.5 middleName_sci^0.6';// which fields to search into, with boost
        }

        if ($lastname) {
            $stringToSearch .= ' ' . $lastname;
            $q['qf'] .= ' lastName_t^1 lastName_s^2';// which fields to search into, with boost
        }

        if ($email) {
            $stringToSearch .= ' ' . $email;
            $q['qf'] .= ' email_s^4';// which fields to search into, with boost
        }


        if ($lastname && $firstname) {

            $authorPhrase = $firstname;
            if ($middlename) {
                $authorPhrase .= ' ' . $middlename;
            }
            $authorPhrase .= ' ' . $lastname;

            $stringToSearch .= ' OR "' . $authorPhrase . '"';


            $q['qf'] .= ' fullName_sci^2'; // boost perfect match
            $q['pf'] = 'fullName_sci^4'; //got both : boost phrase match
        }


        $q['q'] = $stringToSearch;


        //$q['bq'] = 'valid_s:VALID^0.2 OR valid_s:INCOMING^0.1'; // query boost by field value
        $q['bq'] = 'valid_s:VALID^0.1'; // query boost by field value
        $q['wt'] = 'json';
        $q['fl'] = 'docid,*_s,score';
        $q['defType'] = 'dismax';
        $q = array_map('trim', $q);

        $query = http_build_query($q);

        $authors = Ccsd_Tools::solrCurl($query, Ccsd_Search_Solr_Indexer_RefAuthor::$_coreName, 'select', null);

        $authorsArr = json_decode($authors, true);

        if ($authorsArr['response']['numFound'] <= 0) {
            return false;
        }

        return $authorsArr['response']['docs'];


    }

}