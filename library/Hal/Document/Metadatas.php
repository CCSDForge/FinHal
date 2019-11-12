<?php

/**
 * @author S. Denoux
 *
 * Hal_Document_Metadatas est une interface pour manipuler les métadonnées d'un document
 *
 * Les métadonnées sont de types de base 'Simple' (Date, Journal, etc) ou 'Complex' (Titre, Keyword, etc) et partage la même interface 'Abstract'
 *
 * Pour s'intégrer au code de Hal_Document, Hal_Document_Metadatas crée les Hal_Meta à partir d'un tableau et inversement
 */

class Hal_Document_Metadatas implements IteratorAggregate
{
    /**
     * Tableau des Hal_Document_Meta
     * Clé (= identifiant de la méta) => Valeur (Hal_Document_Meta_*)
     * Il n'y a toujours qu'1 Object associée à 1 Clé.
     * Les Hal_Document_Meta_* peuvent être des valeurs simples (Date ou Journal) ou un sous-ensemble de Hal_Document_Meta (Titre, Keyword, etc)
     */
    protected $_metas = [];

    // Table des métadonnées d'un document
    const TABLE_META = 'DOC_METADATA';

    protected $_db;

    /**
     * @return ArrayIterator|Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->_metas);
    }
    /**
     */
    public function __construct()
    {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists($name) {
        return array_key_exists($name, $this->_metas);
    }
    /**
     *  Chargement des métadonnées depuis la base
     * @param $docid
          */
    public function load($docid)
    {
        $db = $this->_db;
        // Chargement des métadonnées
        $sql = $db->select()
            ->from(self::TABLE_META)
            ->where('DOCID = ?', (int)$docid)
            ->order('METAGROUP ASC');

        $result = $db->fetchAll($sql);
        $this->addMetasFromDB($result);
        // Chargement des identifiants externes
        $identifiers = Hal_Document_Meta_Identifier::load($docid);
        if ($identifiers) {
            $this->addMetaObj($identifiers);
            // Chargement des liens externes
            $linkext = Hal_Document_Meta_LinkExt::load($identifiers);
            if ($linkext !== null) {
                $this->addMetaObj($linkext);
            }
            // Chargement des données de recherche
            // $researchdata = Hal_Document_Meta_Researchdata::load($identifiers);
            $researchdata = null;
            if ($researchdata !== null) {
                $this->addMetaObj($researchdata);
            }
        }

        // Old method
        // $sql = $db->select()
        //   ->from(self::TABLE_COPY)
        //   ->where('DOCID = ?', (int)$docid);

        // foreach ($db->fetchAll($sql) as $row) {
        //    $this->createMeta('identifier', $row['LOCALID'], $row['CODE'], $row['SOURCE'], $row['UID'], 1);
        //}
    }

    /**
     * Enregistrement des métadonnées en base
     * @param $docid
     * @param $sid
     */
    public function save($docid, $sid)
    {
        // Récupération des métadonnées à supprimer
        $sqlrequest = $this->_db->select()->from(self::TABLE_META)->where('SID = ' . $sid . ' OR SID = \'null\'')->where('DOCID = ' . $docid);
        $metas = $this->_db->fetchAll($sqlrequest);
        $metasIds = null;

        foreach ($metas as $id) {
            $metasIds[] = $id['METAID'];
        }


        //Suppression des métadonnée en base (si elle existe)
        $this->_db->delete(self::TABLE_META, 'DOCID = ' . $docid . ' AND (SID = ' . $sid . ' OR SID = \'null\')');

        //Cas des identifiants externes (DOI, arxivId, ...)
        $this->_db->delete(Hal_Document_Meta_Identifier::TABLE_COPY, 'DOCID = ' . $docid);

        // Ajout de la date de publication à l'année courante, en cas de métadonnée "A Paraitre"
        if (array_key_exists("inPress", $this->_metas) && $this->_metas["inPress"]->getValue() && (!isset($this->_metas['date']) || empty($this->_metas['date']->getValue()))){
            $this->createMeta('date', date("Y"), '', "web", Hal_Auth::getUid());
        }

        //Enregistrement en base
        foreach ($this->_metas as $halMeta) {
            $halMeta->save($docid, $sid, $metasIds);
        }
        // TODO: BUG: On est en train de sauvegarder, pas de charger ou mettre a jour!!!
        //Récupération du lien extérieur si DOI présent
        if(isset($this->_metas['identifier'])) {
            $metadoi = $this->_metas['identifier']->getValue('doi');
            if ($metadoi != null) {
                $linkext = Hal_LinkExt::load($metadoi);
                if (!$linkext) {
                    $linkext = new Hal_LinkExt('doi', $metadoi,'');
                }
                $linkext->retreiveUrl('doi');
                $this->createMeta('LINKEXT', $linkext, '', "web", Hal_Auth::getUid());
            }
        }
    }

    /**
     * Création d'une nouvelle métadonnée et vérification de sa validité
     * @param $key
     * @param $value
     * @param $group
     * @param $source
     * @param $modifUid
     * @param $status
     * @uses Hal_Document_Meta_Abstract
     */
    public function createMeta($key, $value, $group, $source, $modifUid, $status = 0) {


        // Il faut que la valeur existe, qu'elle ne soit pas vide "" ou [] mais si c'est 0 que ça puisse fonctionner
        if (isset($key) && isset($value)
            && array_key_exists($key, Hal_Document_Meta_Metalist::key2class())
            && (! Ccsd_Tools::isEmpty($value))) {
            $metaClass = 'Hal_Document_Meta_' . ucfirst(Hal_Document_Meta_Metalist::key2class()[$key]);

            /** @var $halMeta Hal_Document_Meta_Abstract */
            $halMeta = new $metaClass($key, $value, $group, $source, $modifUid, $status);
            if ($halMeta->isValid()) {
                if (!array_key_exists($key, $this->_metas)) {
                    $this->_metas[$halMeta->getKey()] = $halMeta;
                } else {
                    $this->_metas[$key]->merge($halMeta);
                }
            }
        }
    }

    /**
     * If $checkvalidity is true, the the validity of meta is checked, return false if non valid
     * Return true in other case
     * @param Hal_Document_Meta_Abstract $meta
     * @param bool $checkValidity
     * @return bool
     */
    public function addMetaObj($meta, $checkValidity=false) {
        $key = $meta -> getKey();
        if ($checkValidity && !$meta->isValid()) {
            return false;
        }
        if (!array_key_exists($key, $this->_metas)) {
            $this->_metas[$key] = $meta;
        } else {
            $this->_metas[$key]->merge($meta);
        }
        return true;
    }
    /**
     * On découpe la métdonnée avant de l'ajouter si elle vient sous forme de tableau
     * @param $key
     * @param $value
     * @param $source
     * @param $modifUid
     */
    public function addMeta($key, $value, $source, $modifUid)
    {
        // Métadonnée simple
        if (!is_array($value)) {
            $this->createMeta($key, $value, '', $source, $modifUid);
            // Métadonnée complexe
        } else {
            foreach ($value as $group => $val) {
                $this->createMeta($key, $val, $group, $source, $modifUid);
            }
        }
    }

    /**
     * On traduit un tableau complet en métas
     * @param $arrayMetas
     * @param $source
     * @param $modifUid
     */
    public function addMetasFromArray($arrayMetas, $source, $modifUid)
    {
        // On ajoute chaque métadonnée passée dans le tableau
        foreach ($arrayMetas as $key => $value) {
            $this->addMeta($key, $value, $source, $modifUid);
        }
    }

    /**
     * On traduit les métadonnées récupérées dans la base en Hal_Meta
     * @param $dbMetas
     */
    public function addMetasFromDB($dbMetas)
    {
        foreach ($dbMetas as $row) {
            $key = $row['METANAME'];
            $value = $row['METAVALUE'];
            $group = $row['METAGROUP'];
            $source = $row['SOURCE'];
            $modifUid = $row['UID'];

            $this->createMeta($key, $value, $group, $source, $modifUid, 1);
        }
    }

    /**
     * On récupère les préférences utilisateur
     * @param Hal_User $user
     */
    public function addMetasFromUser(Hal_User $user)
    {
        if ($user != null) {
            //Pas de discipline, on récupère les disciplines par défaut
            $this->addMeta('domain', $user->getDomain(), "web", $user->getUid());
        }
    }

    /**
     * Transformation des métas en tableau à x profondeur
     * @deprecated  utiliser getHalMeta()
     *          Attention, toutefois, HalMeta ne rends pas de valeur par defaut si la meta n'existe pas
     * @param $name
     * @param bool $group
     * @return array|string
     */
    public function getMeta($name = null, $group = false)
    {

        // On rend la métadonnée filtrée
        if (array_key_exists($name, $this->_metas)) {
            return $this->_metas[$name]->getValue($group);
        }

        // Si on a choisit un filtre mais la métadonnée est vide : on rend la valeur par défaut de la méta
        if (key_exists($name, Hal_Document_Meta_Metalist::key2class())) {
            /** @var  $metaClass Hal_Document_Meta_Abstract */
            $metaClass = 'Hal_Document_Meta_' . ucfirst(Hal_Document_Meta_Metalist::key2class()[$name]);
            return $metaClass::getDefaultValue($group);

        // On rend une string vide si le filtre choisi n'est pas une métadonnée connue
        } else if ($name) {
            return "";

        // On rend l'intégralité des métadonnées sous forme de tableau
        } else {
            $allMetas = [];
            foreach ($this->_metas as $key => $meta) {
                $allMetas[$key] = $meta->getValue();
            }

            return $allMetas;
        }
    }

    /**
     * On rend la méta sous forme d'object
     * @param $name
     * @return Hal_Document_Meta_Abstract | null
     */
    public function getHalMeta($name)
    {
        if (array_key_exists($name, $this->_metas)) {
            return $this->_metas[$name];
        }

        return null;
    }

    /**
     * On remplace une métadonnée par une nouvelle
     * @param $key
     * @param $value
     * @param $source
     * @param $modifUid
     */
    public function setMeta($key, $value, $source, $modifUid)
    {
        // On supprime la valeur précédente liée à cette clé
        $this->delMeta($key);
        $this->addMeta($key, $value, $source, $modifUid);
    }

    /**
     * @param $key
     */
    public function delMeta($key)
    {
        if (array_key_exists($key, $this->_metas)) {
            unset($this->_metas[$key]);
        }
    }

    /**
     * On supprime toutes les métadonnées sauf celle filtrée
     * @param $filter : méta qu'on ne veut pas supprimer
     */
    public function clearMetas($filter = '')
    {
        $interMetas = array();

        if (array_key_exists($filter, $this->_metas)) {
            $interMetas[$filter] = $this->_metas[$filter];
        }
        $this->_metas = $interMetas;
    }

    /**
     * @deprecated
     * POURRA ETRE SUPPRIMEE c'est simplement pour faciliter la transition dans Hal_Document
     */
    public function toArray()
    {
        return $this->getMeta();
    }

    /**
     * @return array
     */
    public function getMetadatas()
    {
        return $this->_metas;
    }

    /**
     * @param Hal_Document_Metadatas $newMetas
     */
    public function merge(Hal_Document_Metadatas $newMetas)
    {
        $nM = $newMetas->getMetadatas();

        foreach ($nM as $key => $value) {
            /** @var Hal_Document_Meta_Abstract $meta */
            if (array_key_exists($key, $this->_metas)) {
                $meta = $this->_metas[$key];
                $meta->merge($value);
            } else {
                $this->_metas[$key] = $value;
            }
        }
    }

    /**
     * @param string $source
     * @return array
     */
    public function getMetaKeysFromSource($source)
    {
        $returnArray = [];

        foreach ($this->_metas as $meta) {

            $k = $meta->getMetasFromSource($source);

            if (!empty($k)) {
                $returnArray[] = $k;
            }
        }

        return $returnArray;
    }
}
