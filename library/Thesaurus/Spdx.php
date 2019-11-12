<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 09/10/2017
 * Time: 16:03
 */

class Thesaurus_Spdx
{
    /**
     * @var string nom du fichier du thésaurus
     * version actuelle 2.6
     * Récupérée https://github.com/sindresorhus/spdx-license-list/blob/master/spdx.json
     */
    private $_srcFilename = 'spdx.json';

    /**
     * @var array Référentiel sous forme d'un tableau
     */
    protected $_data = [];

    /**
     * chemin vers le référentiel au format json
     * @var string
     */
    protected $_filesrc = '';


    public function __construct($load = true)
    {
        $this->_filesrc = __DIR__ . DIRECTORY_SEPARATOR . $this->_srcFilename;
        if ($load) {
            $this->loadThesaurus();
        }
    }

    /**
     * Retourne le tableau des données du thésaurus
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Chargement du fichier json dans un tableau associatif
     */
    protected function loadThesaurus()
    {
        if (is_file($this->_filesrc)) {
            $this->_data = json_decode(file_get_contents($this->_filesrc));
        }
    }

    /**
     * Aide à la saisie, autocompletion
     * @param $q
     * @return array
     */
    public function autocomplete($q)
    {
        $q = strtolower($q);
        $result = [];

        foreach ($this->_data as $licence => $spec) {
            if (! isset ($spec->name)) {
                continue;
            }
            if (strpos(strtolower($licence), $q) !== false || strpos(strtolower($spec->name), $q) !== false) {
                $result[] = $spec->name;
            }
        }
        return $result;
    }

    /**
     * Récupération de l'URL d'une licence à partir de son nom
     * @param $name
     * @return null
     */
    public function getUrl($name)
    {
        foreach ($this->_data as $licence => $spec) {
            if (! isset ($spec->name) || ! isset ($spec->url)) {
                continue;
            }
            if (strtolower($name) == strtolower($licence) || strtolower($name) == strtolower($spec->name)) {
                return $spec->url;
            }
        }
        return null;
    }

    /**
     * Récupération du nom d'une licence à partir de son url
     * @param $url
     * @return null
     */
    public function getLicence($url)
    {
        foreach ($this->_data as $spec) {
            if (strtolower($url) == strtolower($spec->url)) {
                return $spec->name;
            }
        }
        return null;
    }

}