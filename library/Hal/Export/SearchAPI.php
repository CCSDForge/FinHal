<?php

/**
 * Export dans un répertoire de cache des fichiers de métadonnées de HAL
 * @author rtournoy
 *
 */
class Hal_Export_SearchAPI extends Ccsd_Export
{
    /**
     * Partie publique du chemin pour le téléchargement
     *
     * @var string
     */
    protected $_filename;

    /**
     * Chemin vers le fichier d'export
     *
     * @var string
     */
    protected $_exportFilePath;

    /**
     * Extension du fichier
     *
     * @var string
     */
    protected $_extension;

    /**
     * Compresse l'export
     *
     * @var boolean
     */
    protected $_compress;

    public function __construct($data, $filename = null, $extension = 'txt', $exportFilePath = null, $compress = false)
    {
        if (!defined('EXPORT_DIR')) {
            throw new Exception ("Export : le répertoire d'export public n'a pas été définit");
        }

        $this->setExportFilePath($exportFilePath);
        $this->setExtension($extension);
        $this->setFilename($filename);
        $this->setCompress($compress);
        parent::setData($data);
    }

    /**
     * Export pour téléchargement direct dans le navigateur
     *
     * @return boolean | string
     */
    public function exportAsAttachment()
    {
        return $this->getData();
    }

    /**
     * Export pour téléchargement après stockage dans un répertoire de cache
     *
     * @throws Exception
     * @return boolean string du fichier à télécharger
     */
    public function exportAsDownload()
    {
        if (!is_writable($this->getExportFilePath())) {

            throw new Exception ('Export : ne peux pas écrire dans le répertoire ' . htmlspecialchars($this->_exportFilePath));
        }

        $result = file_put_contents($this->getExportFilePath() . $this->getFilename(), $this->getData());

        if (!$result) {
            throw new Exception ("Export : erreur lors de l'écriture du fichier " . htmlspecialchars($this->getExportFilePath() . $this->getFilename()));
        }

        $filename = '/public/export/' . $this->getFilename();

        if ($this->getCompress()) {
            $zip = new ZipArchive ();
            if ($zip->open($this->getExportFilePath() . $this->getFilename() . '.zip', ZipArchive::CREATE) !== TRUE) {
                // echec creation archive mais retourne quand même le fichier non compressé
                return $filename;
            }

            $zip->addFile($this->getExportFilePath() . $this->getFilename(), $this->getFilename());
            $zip->close();
            $filename .= '.zip';
        }

        return $filename;

    }

    /**
     *
     * @return string $_exportFilePath
     */
    public function getExportFilePath()
    {
        return $this->_exportFilePath;
    }

    /**
     * Définit le chemin vers le répertoire d'export, essaie de le créer s'il
     * n'existe pas
     *
     * @param string $_exportFilePath         chemin vers le répertoire d'export
     * @return Hal_Export_SearchAPI
     */
    private function setExportFilePath($_exportFilePath = null)
    {

        $_exportFilePath = str_replace(array('.', '..'), '', $_exportFilePath);

        if (is_dir($_exportFilePath)) {
            $this->_exportFilePath = $_exportFilePath;
        } else {
            $this->_exportFilePath = EXPORT_DIR;
        }

        if (!is_dir($this->_exportFilePath)) {
            $resMkdir = @mkdir($this->_exportFilePath, 0755, true);

            if (!$resMkdir) {
                throw new Exception ('Export : le répertoire ' . htmlspecialchars($this->_exportFilePath) . " n'existe pas et n'a pas pu être créé automatiquement.");
            }
        }

        return $this;
    }

    /**
     *
     * @return string $_filename
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     *
     * @param string $_filename    sans l'extension
     * @return Hal_Export_SearchAPI
     */
    public function setFilename($_filename = null)
    {
        if ($_filename == null) {
            $this->_filename = date("Ymd-His") . '-' . Zend_Registry::get('website')->getSiteName();
            $this->_filename .= '-export-' . uniqid() . $this->getExtension();
        } else {
            $this->_filename = $_filename . $this->getExtension();
        }

        return $this;
    }

    /**
     *
     * @return string $_extension
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     *
     * @param string $_extension
     * @return Hal_Export_SearchAPI
     */
    public function setExtension($_extension)
    {
        $_extension = ltrim($_extension, '.');
        $this->_extension = '.' . $_extension;
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function getCompress()
    {
        return $this->_compress;
    }

    /**
     *
     * @param bool $_compress
     * @return Hal_Export_SearchAPI
     */
    public function setCompress($_compress)
    {
        switch ($_compress) {
            case true :
            case false :
                $this->_compress = $_compress;
                break;
            default :
                $this->_compress = false;
                break;
        }

        return $this;
    }
}























