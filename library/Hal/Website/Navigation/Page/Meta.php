<?php

/**
 * Class Hal_Website_Navigation_Page_Meta
 */
class Hal_Website_Navigation_Page_Meta extends Hal_Website_Navigation_Page {

    protected $_controller = 'browse';
    protected $_action = 'meta';
    protected $_meta = '';
    protected $_multiple = true;

    const SCHEMA_FIELDS_LIST_CACHE = 86700;

    /**
     * Tri utilisateur
     * @var string
     */
    protected $_sort;
    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [ 'meta' => 'setMeta', 'sort' => 'setSort' ];
    /**
     * Chargement de la page
     *
     * @see Ccsd_Website_Navigation_Page::load()
     */
    public function load() {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from('WEBSITE_NAVIGATION', 'PARAMS')->where('SID = ?', $this->getSid())->where('TYPE_PAGE = ?', __CLASS__)->where ( 'ACTION = ?', 'meta-' . $this->getMeta() );
        $options = $db->fetchOne($sql);

        if ($options) {
            $this->setOptions(unserialize($options));
            return $this;
        }
        return null;
    }
    /**
     *
     * @return string[]
     */
    private function getMetadataFields() {
        $rhey = [];

        $sc = new Ccsd_Search_Solr_Schema([
            'env' => APPLICATION_ENV,
            'core' => 'hal',
            'handler' => 'schema'
        ]);

        $sc->getSchemaFieldsByType([
            'string'
        ]);

        $fieldBlacklist = [
            'abstract_s',
            'anrProjectValid_s',
            'arxivId_s',
            'audience_s',
            'authBlogIdExt_s',
            'authFacebookIdExt_s',
            'authFirstName_s',
            'authFullName_s',
            'authIdHal_s',
            'authIdrefIdExt_s',
            'authIsniIdExt_s',
            'authLastName_s',
            'authLinkedinIdExt_s',
            'authMiddleName_s',
            'authOrcidIdExt_s',
            'authOrganism_s',
            'authQuality_s',
            'authResearcheridIdExt_s',
            'authTwitterIdExt_s',
            'authUrl_s',
            'authViafIdExt_s',
            'baseMap_s',
            'bibcodeId_s',
            'mainFile_s',
            'citationFull_s',
            'citationRef_s',
            'collaboration_s',
            'collCategory_s',
            'collCode_s',
            'collName_s',
            'domainAllCode_s',
            'comment_s',
            'conferenceEndDate_s',
            'conferenceStartDate_s',
            'contributorFullName_s',
            'coordinates_s',
            'credit_s',
            'deptStructAcronym_s',
            'deptStructAddress_s',
            'deptStructCode_s',
            'deptStructCountry_s',
            'deptStructName_s',
            'deptStructType_s',
            'deptStructValid_s',
            'description_s',
            'doiId_s',
            'domain_s',
            'ensamIdhalId_s',
            'invitedCommunication_s',
            'europeanProjectEndDate_s',
            'europeanProjectFinancing_s',
            'europeanProjectStartDate_s',
            'europeanProjectValid_s',
            'fileAnnexes_s',
            'fileMain_s',
            'fileMainAnnex_s',
            'files_s',
            "fileAnnexesAudio_s",
            "fileAnnexesFigure_s",
            "fileAnnexesVideo_s",
            'halId_s',
            'instStructAcronym_s',
            'instStructAddress_s',
            'instStructCode_s',
            'instStructCountry_s',
            'instStructName_s',
            'instStructType_s',
            'instStructValid_s',
            'irdId_s',
            'irsteaId_s',
            'isbn_s',
            'journalDate_s',
            'journalDoiRoot_s',
            'journalEissn_s',
            'journalIssn_s',
            'journalSherpaCondition_s',
            'journalSherpaPreRest_s',
            'journalSherpaDate_s',
            'journalTitleAbbr_s',
            'journalUrl_s',
            'journalValid_s',
            'label_bibtex',
            'label_coins',
            'label_endnote',
            'label_s',
            'label_xml',
            'labStructAcronym_s',
            'labStructAddress_s',
            'labStructCode_s',
            'labStructCountry_s',
            'labStructName_s',
            'labStructType_s',
            'labStructValid_s',
            'lectureCategory_s',
            'lectureContext_s',
            'level0_domain_s',
            'level1_domain_s',
            'level2_domain_s',
            'level3_domain_s',
            'levelTraining_s',
            'library_s',
            'nntId_s',
            'number_s',
            'oataoId_s',
            'okinaId_s',
            'page_s',
            'peerReviewing_s',
            'popularLevel_s',
            'presConfType_s',
            'primaryDomain_s',
            'proceedings_s',
            'prodinraId_s',
            'producedDate_s',
            'publicationLocation_s',
            'publisher_s',
            'publisherLink_s',
            'pubmedcentralId_s',
            'pubmedId_s',
            'related_s',
            'relator_s',
            'rteamStructAcronym_s',
            'rteamStructAddress_s',
            'rteamStructCode_s',
            'rteamStructCountry_s',
            'rteamStructName_s',
            'rteamStructType_s',
            'rteamStructValid_s',
            'scale_s',
            'sciencespoId_s',
            'seeAlso_s',
            'structAcronym_s',
            'structAddress_s',
            'structCode_s',
            'structCountry_s',
            'structName_s',
            'structType_s',
            'structValid_s',
            'type_s',
            'typeAnnex_s',
            'uri_s',
            'authArxivIdExt_s',
            "rteamStructIdrefIdExt_s",
            "rteamStructRnsrIdExt_s",
            "rteamStructIsniIdExt_s",
            "deptStructIdrefIdExt_s",
            "deptStructRnsrIdExt_s",
            "deptStructIsniIdExt_s",
            "labStructIdrefIdExt_s",
            "labStructRnsrIdExt_s",
            "labStructIsniIdExt_s",
            "instStructIdrefIdExt_s",
            "instStructRnsrIdExt_s",
            "instStructIsniIdExt_s",
            "structIdrefIdExt_s",
            "structRnsrIdExt_s",
            "structIsniIdExt_s",
        ];

        $translator = Ccsd_Form::getDefaultTranslator();
        foreach ($sc->getFields() as $field) {
            if (!in_array($field, $fieldBlacklist)) {
                $fieldIndex = substr_replace($field, '', - 2, 2);
                $rhey [$fieldIndex] = strip_tags($translator->translate('hal_' . $field));
            }
        }

        $dfFieldBlacklist = [
            '*_subTitle_s',
            '*_domain_s',
            '*_title_s',
            '*_abstract_s',
            '*_keyword_s',
            '*IdExt_s',
            '*Id_s'
        ];

        $sc->getSchemaDynamicFields();
        foreach ($sc->getDynamicFields() as $dfield) {
            $dfield = (array) $dfield;

            if (!in_array($dfield ["name"], $dfFieldBlacklist)) {
                if ($dfield ['type'] == 'string') {
                    foreach ($dfield ['fieldList'] as $fieldList) {
                        $fieldIndex = substr_replace($fieldList, '', - 2, 2);
                        $rhey [$fieldIndex] = strip_tags($translator->translate('hal_' . $fieldList));
                    }
                }
            }
        }
        uasort($rhey, 'strcoll');
        return $rhey;
    }

    /**
     * Retour du formulaire de création de la page
     * @param int $pageidx
     * @return Ccsd_Form
     * @see Ccsd_Website_Navigation_Page::getForm()
     */
    public function getForm($pageidx) {
        parent::getForm($pageidx);

        $cacheName = Ccsd_Cache::makeCacheFileName(__CLASS__);

        $metaDataFields = null;

        if (Hal_Cache::exist($cacheName, self::SCHEMA_FIELDS_LIST_CACHE)) {
            $metaDataFields = Hal_Cache::get($cacheName);
            if (false != $metaDataFields) {
                $metaDataFields = unserialize($metaDataFields);
            }
        }

        if ($metaDataFields == null) {
            $metaDataFields = $this->getMetadataFields();
            Hal_Cache::save($cacheName, serialize($metaDataFields));
        }
        try {
            $this->_form->addElement('select', 'meta', [
                'required' => true,
                'label' => 'Métadonnée à utiliser',
                'class' => '',
                'value' => $this->getMeta(),
                'multioptions' => $metaDataFields,
                'belongsTo' => 'pages_' . $pageidx
            ]);
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add select element to form");
        }
        try {
            $this->_form->addElement('radio', 'sort', [
                'label' => 'Tri par défaut des résultats',
                'description' => "Trier par nombre de documents ou ordre alphabétique, l'utilisateur peut changer le tri dans l'interface",
                'value' => $this->getSort(),
                'multioptions' => [
                    'index' => 'Tri par ordre alphabétique',
                    'count' => 'Tri par nombre de documents'
                ],
                'belongsTo' => 'pages_' . $pageidx
            ]);
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add radio element to form");
        }
        return $this->_form;
    }

    /**
     * Retourne les informations complémentaires spécifiques à la page
     *
     * @see Ccsd_Website_Navigation_Page::getSuppParams()
     */
    public function getSuppParams() {
        $res = '';
        if ($this->_meta != '') {
            $res = serialize([
                'meta' => $this->getMeta(),
                'sort' => $this->getSort()
            ]);
        }
        return $res;
    }

    /**
     * Conversion de la page en tableau associatif
     *
     * @see Ccsd_Website_Navigation_Page::toArray()
     */
    public function toArray() {
        $array = parent::toArray();
        $array ['meta'] = $this->getMeta();
        $array ['sort'] = $this->getSort();
        return $array;
    }

    /**
     *
     * @return string
     */
    public function getMeta() {
        return $this->_meta;
    }

    /**
     *
     * @param string $_meta
     * @return Hal_Website_Navigation_Page_Meta
     */
    public function setMeta($_meta) {
        $this->_meta = $_meta;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->_action . '-' . $this->_meta;
    }

    /**
     * Retourne le tri de l'utilisateur
     */
    public function getSort() {
        if ($this->_sort == null) {
            return 'index';
        }
        return $this->_sort;
    }

    /**
     * Définit le tri de l'utilisateur
     *
     * @param string $_sort
     * @return Hal_Website_Navigation_Page_Meta
     */
    public function setSort($_sort = 'index') {
        $this->_sort = $_sort;
        return $this;
    }

}
