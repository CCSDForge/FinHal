<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 06/01/17
 * Time: 14:23
 */
class Hal_Document_Tei
{
    protected $_doc;

    public function __construct($doc)
    {
        $this->_doc = $doc;
    }

    public function create()
    {
        $tei = new Hal_Document_Tei_Creator($this->_doc);
        return $tei->create();
    }

    public function load($xml, $pathImport, $instance)
    {
        $loader = new Hal_Document_Tei_Loader($xml);

        // Chargement des métadonnées
        $metas = $loader->loadMetas($instance);
        // TO DO : TypDoc est pour l'instant traité à part des métadonnées...
        $this->_doc->setTypdoc($metas["typdoc"]);
        unset($metas["typdoc"]);
        $this->_doc->setMetas($metas);

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
            /**
             * @var $author Hal_Document_Author
             */

            // On fait correspondre les structures du document
            $autIdx = $author->getStructidx();
            $author->setStructidx([]);

            foreach ($autIdx as $idx) {
                $author->addStructidx($structCorrespondance[$idx]);
            }

            $authorIdx = $this->_doc->addAuthor($author);
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
