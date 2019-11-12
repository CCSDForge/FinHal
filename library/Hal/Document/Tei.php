<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 06/01/17
 * Time: 14:23
 */
class Hal_Document_Tei
{

    const LOAD_AFFIL_OPT = 'loadAffiliation';

    /** @var  Hal_Document */
    protected $_doc;
    /** @var array string[] */
    protected $options = [];

    /**
     * Hal_Document_Tei constructor.
     * @param Hal_Document  $doc
     */
    public function __construct($doc)
    {
        $this->_doc = $doc;
    }

    /**
     * @param string $option
     * @param mixed $value
     */
    public function setOption($option, $value) {
        switch ($option) {
            case self::LOAD_AFFIL_OPT:
                $this->options[self::LOAD_AFFIL_OPT] =  (bool) $value;
                break;
            default:
                break;
        }
    }
    /**
     * @param mixed[] $options
     */
    public function setOptions($options) {
        foreach ($options as $opt =>$val) {
            $this->setOption($opt,$val);
        }
    }
    /**
     * @return bool
     */
    public function isOptionLoadAffiation() {
        if (array_key_exists(self::LOAD_AFFIL_OPT, $this->options)) {
            return $this->options[self::LOAD_AFFIL_OPT];
        } else {
            return true;
        }
    }

    /**
     * @return string
     */
    public function create()
    {
        $tei = new Hal_Document_Tei_Creator($this->_doc);
        $dom = $tei->createDOM();
        /** les chaine XML ne doivent jamais contenir le <?xml ...: il faut l'ajouter seulement qd on sert reelement un XML
         * de cette facon, on peut concatener des xml tei du cache.
         */
        return $dom->saveXML($dom ->documentElement);
    }

    /**
     * Contruit le document associe a partir d'un DOM Tei
     * @param DOMDocument $xml
     * @param string $pathImport
     * @param string $instance
     * @throws Hal_Site_Exception
     */
    public function load($xml, $pathImport, $instance)
    {
        $loader = new Hal_Document_Tei_Loader($xml);

        // Chargement des métadonnées
        $metas = $loader->loadMetas($instance);
        // TO DO : TypDoc est pour l'instant traité à part des métadonnées...
        $this->_doc->setTypdoc($metas["typdoc"]);
        unset($metas["typdoc"]);
        $this->_doc->setMetas($metas);

        if ($this->isOptionLoadAffiation()) {
            // Chargement des auteurs
            $authAndStruct = $loader->loadAuthorsAndStructures();
            $authors = $authAndStruct['authors'];
            $structures = $authAndStruct['structures'];

            // On fait une correspondance entre les identifiants des structures et leur identifiant une fois ajouté au document (une fois dédoublonné)
            $structCorrespondance = [];
            $this->_doc->delAutStruct();
            foreach ($structures as $idx => $struct) {
                $structidx = $this->_doc->addStructure($struct);

                $structCorrespondance[$idx] = $structidx;
            }

            foreach ($authors as $author) {
                /** @var $author Hal_Document_Author */
                // On fait correspondre les structures du document
                $autIdx = $author->getStructidx();
                $author->setStructidx([]);

                foreach ($autIdx as $idx) {
                    $author->addStructidx($structCorrespondance[$idx]);
                }
                $this->_doc->addAuthor($author);
            }
        }
        // Chargement des ressources liées
        $this->_doc->setRelated($loader->loadRessources());

        //Chargement des fichiers
        $files = $loader->loadFiles($pathImport);
        if ( count($files) == 1 ) {
            $files[0]->setDefault(true);
        }
        $this->_doc->setFiles($files);

        //Chargement des collections
        $this->_doc->setCollections($loader->loadCollections($this->_doc->getContributor('uid')));
    }
}
