<?php


/**
 * Fichier associé à un document
 *
 */
class Hal_Document_File 
{

    const TABLE	=	'DOC_FILE';

	/**
	 * Identifiant du document
	 * @var int
	 */
	protected $_fileid = 0;
	
	/**
	 * Nom du fichier
	 * @var string
	 */
	protected $_name = '';
	
	/**
	 * Format du fichier (vidéo, ...)
	 * @var string
	 */
	protected $_format = '';

    /**
     * Origine du fichier (fichier auteur, ...)
     * @var string
     */
    protected $_origin = '';

    /**
     * Type du fichier (fichier, source, annexe)
     * @var string
     */
    protected $_type = '';
	
	/**
	 * Commentaire du fichier
	 * @var string
	 */
	protected $_comment = '';

    /**
     * Imagette
     * @var string
     */
    protected $_imagette = 0;

    /**
     * Imagette temporaire pour l'interface de dépot
     * @var string
     */
    protected $_tmpThumb = '';

    /**
     * Extension
     * @var string
     */
    protected $_extension = '';
	
	/**
	 * Lien vers le document
	 * @var string
	 */
	protected $_path = '';
	
	/**
	 * Type MIME du document
	 * @var string
	 */
	protected $_typeMIME = '';
	
	/**
	 * Taille du fichier en bytes
	 * @var int
	 */
	protected $_size = 0;
	
	/**
	 * Md5 du fichier
	 * @var string
	 */
	protected $_md5 = '';

    /**
     * Source du fichier : déposé par l'auteur, compilé par nos soins, convertis par nos soins
     * @var string
     */
    protected $_source = self::SOURCE_AUTHOR;

    const SOURCE_AUTHOR = 'author';
    const SOURCE_COMPILED = 'compilation';
    const SOURCE_CONVERTED = 'converted';
    const SOURCE_UNZIPPED = 'unzipped';

	/**
	 * Inidique la date de visibilité du fichier
	 * @var string // date
	 */
	protected $_dateVisible = '';

    /**
     * Fichier annexe principal
     * @var bool
     */
    protected $_defaultAnnex = false;
	
	/**
	 * Fichier principal
	 * @var boolean
	 */
	protected $_default = false;
	
	/**
	 * Lien vers le fichier converti
	 * @var boolean
	 */
	protected $_convertFile = false;
	
	/**
	 * Constructeur
	 * @param int $fileid
	 */

    private $_copy_handler = 'rename';

    /**
     * Hal_Document_File constructor.
     * @param array $data
     * @param int $fileid
     */
	public function __construct($fileid = 0, $data = [])
	{
		if (!empty($data)) {
		    $this->set($data);
        } else {
            $this->setFileid($fileid);
        }
	} 
	
	/**
	 * Initialisation de l'objet à partir de l'upload
	 * @param array $data
	 */
	public function set($data)
	{
		foreach ($data as $attrib => $value) {
			$this->{'_' . $attrib} = $value;	
		}
	}

    /**
     * Définitiion de l'identifiant du fichier
     * @param int
     */
    public function setFileid($fileid)
    {
        $this->_fileid = $fileid;
    }

    /**
     * @return int
     */
    public function getFileid()
    {
        return $this->_fileid;
    }
	/**
	 * Définition du format du fichier
	 * @param string $format
	 */
	public function setFormat($format)
	{
		$this->_format = $format;
	}
	
	/**
	 * Récupération du format
	 * @return string
	 */
	public function getFormat()
	{
		return $this->_format;
	}

    /**
     * Définition de l'origine du fichier
     * @param string $origin
     */
    public function setOrigin($origin)
    {
        $this->_origin = $origin;
    }

    /**
     * Récupération de l'origine du fichier
     * @return string
     */
    public function getOrigin()
    {
        return $this->_origin;
    }

    /**
     * @param string
     */
    public function setSource($source)
    {
        // todo : renvoyer une exception si la source ne correspond pas aux 3 possibles ?
        $this->_source = $source;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Définition du type du fichier
     * @param string $type
     */
    public function setType($type)
    {
        $this->_type = $type;
    }

    /**
     * Récupération du type
     * @return string
     */
        public function getType()
    {
        return $this->_type;
    }

	/**
	 * Définition du nom du fichier
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->_name = $name;
	}
	
	/**
	 * Récupération du nom du fichier
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}
	
	/**
	 * Définition du lien vers le fichier
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->_path = $path;
	}
	
	/**
	 * Récupération du chemin vers le fichier
     * @param bool $omitPathDoc
	 * @return string
	 */
	public function getPath($omitPathDoc = false)
	{
		if ($omitPathDoc) {
            return str_replace(PATHDOCS, '', $this->_path);
        }
        return $this->_path;
	}
	
	/**
	 * Définition du type MIME du fichier
	 * @param string $typeMIME
	 */
	public function setTypeMIME($typeMIME)
	{
		$this->_typeMIME = $typeMIME;
	}
	
	/**
	 * Récupération du type MIME du fichier
	 * @return string
	 */
	public function getTypeMIME()
	{
		return $this->_typeMIME;
	}
	
	/**
	 * Définition du commentaire
	 * @param string $comment
	 */
	public function setComment($comment)
	{
		$this->_comment = $comment;
	}
	
	/**
	 * Récupération du commentaire associé au fichier
	 * @return string
	 */
	public function getComment()
	{
		return $this->_comment;
	}
	
	/**
	 * Définition de la taille du fichier
	 * @param int $size
     * @return int
	 */
	public function setSize($size = -1)
	{
        if ($size == -1) {
            $this->_size = filesize($this->getPath());
        }  else {
            $this->_size = $size;
        }
        return $this->_size;
	}
	
	/**
	 * Récupération de la taille du fichier
	 * @param bool $bytes
	 * @return number|string
	 */
	public function getSize($bytes = false)
	{
		if ($bytes) {
			return $this->_size;
		} else {
			return Ccsd_File::convertFileSize($this->_size);
		}
	}

    /**
     * @return bool
     */
    public function file_exists() {
        return file_exists($this -> getPath());
    }
    
	/**
	 * Definition du fichier principal
	 * @param bool $default
	 */
	public function setDefault($default)
	{
		$this->_default = $default;
	}

    /**
     * @return bool
     */
    public function canRead()
    {
        return $this->getDateVisible() <= date('Y-m-d');
    }

    /**
     * @param string $imagette
     */
    public function setImagette($imagette)
    {
        $this->_imagette = $imagette;
    }

    /**
     * @return string
     */
    public function getImagette()
    {
        return $this->_imagette;
    }

    /**
     * @param string $format
     * @return string
     */
    public function getImagetteUrl($format = 'small')
    {
        return THUMB_URL . '/' . $this->getImagette() . '/' . $format;
    }

    /**
     * @param string $path
     * @return string
     * @throws ImagickException
     */
    protected function getImageBlob($path)
    {
        $im = new Imagick();
        $im->setResolution(300, 300);
        // On prend la première page du pdf
        $im->readimage(realpath($path) . '[0]');
        $im->setImageFormat('png');

        $blob = "data:image/png;base64,".base64_encode($im->getImageBlob());

        $im->clear();
        return $blob;
    }

    /**
     * @throws ImagickException
     */
    public function getTmpThumb()
    {
        if (!empty($this->_tmpThumb)) {
            return $this->_tmpThumb;
        }

        try {
            $this->_tmpThumb = $this->getImageBlob($this->_path);
            return $this->_tmpThumb;
        } catch (Exception $e) {
            // Envoie de l'imagette par défaut
            return $this->getImageBlob(APPROOT.'/'.PUBLIC_DEF.'img/defaultThumb.jpg');
        }
    }
	
	/**
	 * Indique si le fichier est le fichier principal du dépôt
	 * @return boolean
	 */
	public function getDefault()
	{
		return $this->_default;
	}

    /**
     * @param bool $defaultAnnex
     */
    public function setDefaultannex($defaultAnnex)
    {
        $this->_defaultAnnex = $defaultAnnex;
    }

    /**
     * @return bool
     */
    public function getDefaultannex()
    {
        return $this->_defaultAnnex;
    }

    /**
     * @param string $ext
     */
    public function setExtension($ext)
    {
        $this->_extension = $ext;
    }

    /**
     * @return string
     */
	public function getExtension()
	{
		return Ccsd_File::getExtension($this->_name);
	}

    /**
     * Affecte la valeur de _dateVisible en la validant.
     * Si la date est superieure au maximum autorise pour le portail,
     * La date est positionnee a max d'embargo pour le portail
     *
     * @param string $dateVisible
     * @return string   // La date reellement positionnee
     */
    public function setDateVisible($dateVisible='')
    {
        if ($dateVisible != '' && $this->isEmbargoValid()) {
            $this->_dateVisible = $dateVisible;
        } else {
            if (!$this->isEmbargoValid()) {
                $this->_dateVisible = $this->maxEmbargo();
            } else {
                $this->_dateVisible = date('Y-m-d');
            }
        }
        return $this->_dateVisible ;
    }

    /**
     * @return string
     */
	public function getDateVisible()
	{
		return ($this->_dateVisible != '') ? $this->_dateVisible : date('Y-m-d');
	}

    /**
     * @return string
     */
	public function getMd5()
	{
		return $this->_md5;
	}

    /**
     * @param string $md5
     */
	public function setMd5($md5)
	{
		$this->_md5 = $md5;
	}

    /**
     * @return bool
     */
	public function isConverted()
	{
		return $this->_convertFile !== false;
	}

    /** setter
     * @param int $id
     */
	public function setConvertFile($id)
	{
		$this->_convertFile = $id;
	}
	
	
	/**
	 * Transformation de l'objet en tableau
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'fileid'	    =>	$this->getFileid(),
			'name'		    =>	$this->getName(),
			'comment'	    =>	$this->getComment(),
			'path'		    =>	$this->getPath(),
			'fileType'      =>	$this->getType(),
			'typeAnnex'	    =>	$this->getFormat(),
			'fileSource'    =>	$this->getOrigin(),
			'extension'	    =>	$this->getExtension(),
			'dateVisible'	=>	$this->getDateVisible(),
			'typeMIME'	    =>	$this->getTypeMIME(),
			'size'		    =>	$this->getSize(),
			'default'	    =>	$this->getDefault(),
			'defaultAnnex'	=>	$this->getDefaultannex(),
			'md5'	        =>	$this->getMd5(),
            'source'        =>  $this->getSource(),
			'imagette'	    =>	$this->getImagette(),
            'imagetteUrl'	    =>	$this->getImagetteUrl(),
		);
	}

    /**
     * A partir de l'URL ftp ccsd, retourne le pathname sur fs local
     * @param string $url
     * @return string
     */
    public function ftpfilename($url) {
        $filename = preg_replace('=ftp://ftp.ccsd.cnrs.fr/+=','',$url);
        return Ccsd_User_Models_User::CCSD_FTP_PATH . Hal_Auth::getUid() . '/' . $filename;
    }

    /**
     * @return bool
     */
    public function ftpSetFileInfos() {
        $url = $this->getPath();
        $filename     = $this -> ftpfilename($url);
        $basefilename = basename($filename);
        if (is_file($filename)) {
            $this->setName($basefilename);
            $this->setPath($filename);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $tmp_dest
     * @return bool
     */
    public function urlSetFileInfos($tmp_dest=null) {
        $path = $this->getPath();
        //Création d'un espace temporaire
        if (!isset($tmp_dest)) {
            $tmp_dest = PATHTEMPDOCS . uniqid() . '/';
        }
        if (!is_dir($tmp_dest)) {
            mkdir($tmp_dest, 0777, true) || error_log("Can't mkdir $tmp_dest .");
        }
        $tmpFile = $tmp_dest . 'tmp.' . Ccsd_File::getExtension($path);

        $fileHandle = fopen($tmpFile, 'w+');
        $curl = curl_init($path);
        set_time_limit(0);
        curl_setopt($curl, CURLOPT_USERAGENT, "CCSD - HAL Proxy");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3600);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FILE, $fileHandle);
        curl_exec($curl);
        curl_close($curl);
        fclose($fileHandle);

        $filename = $contentType = '';
        if (filesize($tmpFile)) {
            if (preg_match('/^ftp/i', $path)){
                $contentType = Ccsd_File::getMimeType($tmpFile);
            } else {
                foreach (get_headers($path) as $header) {
                    if (preg_match('/^Content-Type: *(.+)$/i', $header, $match)) {
                        $contentType = trim($match[1]);
                    }
                    if (preg_match('/^Content-Disposition: *[a-z]+; *filename=(.+)$/i', $header, $match)) {
                        $filename = trim(html_entity_decode(mb_convert_encoding($match[1], 'UTF-8', 'UTF-8,ISO-8859-1')), ' ".');
                    }
                }
            }

            if ($filename == '') {
                $filename = basename($path);
            }
            $this->setTypeMIME($contentType);
            $this->setName($filename);
            $this->setPath($tmpFile);
                
            return true;
        }

        return false;
    }

    /**
     * @param string  $pathImport
     * @return bool
     */
    public function localSetFileInfos($pathImport = null)
    {
        $isFile = is_file($this->getPath());

	    if (null == $pathImport || $isFile) {
            return true;
        }

        $newPath = $pathImport . '/' .$this->getName();
        if (is_file($newPath)) {
            $this->setPath($newPath);
            return true;
        }

        //Cas des fichiers dans des répertoires
        $newPath = $pathImport . '/' .$this->getPath();
        if (is_file($newPath)) {
            $this->setPath($newPath);
            return true;
        }

        return false;
    }

    /**
     * @param string $pathImport
     * @return bool
     */
    public function setFileInfos($pathImport = null) {
        // Recupere les fichiers si Url externe
        // Positionne le mode de recopie (copy ou rename)
        // retourne faux si le fichier souhaite n'est en fin de compte pas au bout de getpath()
        if (preg_match("~^ftp://ftp.ccsd.cnrs.fr~i", $this->getPath())) {
            // Ftp interne
            $this -> _copy_handler = 'copy';
            return $this->ftpSetFileInfos();
        }
        if (preg_match("~^(?:f|ht)tps?://~i", $this->getPath())) {
            // Url externe
            $this -> _copy_handler = 'rename';
            return $this->urlSetFileInfos();
        }

        // Fichier local
        $this -> _copy_handler = 'rename';
        return $this->localSetFileInfos($pathImport);
    }

    /**
     * Conversion en JPEG si le fichier
     * @return Hal_Document_File|null
     */
    private function convertIfNecessary()
    {
        // On converti un fichier par défaut du type IMAGE qui n'est pas déjà en jpeg
        if ( $this->getDefault() && in_array(strtolower($this->getExtension()), Hal_Settings::getMainFileType('IMG')) && !in_array(strtolower($this->getExtension()), ['jpeg','jpg'])) {

            $tmpDir = dirname($this->getPath()).'/';
            $convertedName = Ccsd_File::convertImg($this->getPath(), $tmpDir);

            if (!$convertedName) {
                return null;
            }

            $filepath = $tmpDir . $convertedName;

            $fileData = array(
                'name' => $convertedName,
                'path' => $filepath,
                'size' => (@filesize($filepath))?filesize($filepath):0,
                'typeMIME' => Ccsd_File::getMimeType($filepath)
            );

            $newFile = new Hal_Document_File(0, $fileData);

            if (!$newFile) {
                return null;
            }

            $newFile->setSource(Hal_Document_File::SOURCE_CONVERTED);
            $newFile->setDefault(true);
            $newFile->setOrigin($this->getOrigin());
            $newFile->setType(Hal_Settings::FILE_TYPE);

            return $newFile;
        }

        return null;
    }


    /**
     * @param int $docid
     * @param string $dest
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     */
    public function save($docid, $dest)
    {
        $dest = rtrim($dest,'/');
        //Récupération du fichier
        
        if ($this -> setFileInfos() === false) {
            // Probleme avec le fichier source
            Ccsd_Log::message("Error save: setFileInfos can't find a good file for " . $this->getPath());
            return false;
        }

        $convertedFile = $this->convertIfNecessary();

        $basename = basename($this->getName());
        $dest .= '/' . dirname($this->getName());
        if (!is_dir($dest)) {
            mkdir($dest, 0777, true);
        }
        
        set_time_limit(0); // Laisser du temps a la copie de gros fichier
        $copyHandler = $this->_copy_handler;
        if (realpath($this->getPath()) != realpath($dest.'/'.$basename) && $copyHandler($this->getPath(), $dest . '/' . $basename) === false) {
            Ccsd_Log::message('Error save copy getPath '. $this->getPath() .' dest '.$dest.
                              ' basename '.$basename, false, '', PATHTEMPDOCS.'file');
            $errors= error_get_last();
            Ccsd_Log::message('Error save copy: '.$errors['type'].' - '.$errors['message'], false, '', PATHTEMPDOCS.'file');
            return false;
        }

        
        if ($this->getSize(true) == 0) {
            $this->setSize(filesize($dest . '/' . $basename));
        }
        if ($this->getTypeMIME() == '') {
            $this->setTypeMIME(Ccsd_File::getMimeType($dest . '/' . $basename));
        }

        if ($convertedFile) {
            // Le fichier converti est considéré comme "fichier source"
            $this->setType(Hal_Settings::FILE_TYPE_SOURCES);

            // Enregistrement du fichier converti
            $convertedFile->save($docid, $dest);
        }

        if ($this->getType() == Hal_Settings::FILE_TYPE) {
            $default = (int)$this->getDefault();
        } else if ($this->getType() == Hal_Settings::FILE_TYPE_ANNEX) {
            $default = (int)$this->getDefaultannex();
        } else {
            $default = 0;
        }

        //Enregistrement en base
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $bind = array(
            'DOCID'       => $docid,
            'TYPEANNEX'   => $this->getFormat(),
            'FILENAME'    => $this->getName(),
            'INFO'        => $this->getComment(),
            'MAIN'        => (int) $default,
            'EXTENSION'   => $this->getExtension(),
            'DATEVISIBLE' => $this->getDateVisible(),
            'TYPEMIME'    => $this->getTypeMIME(),
            'SIZE'        => $this->getSize(true),
            'FILETYPE'    => $this->getType(),
            'SOURCE'      => $this->getSource(),
            'FILESOURCE'  => $this->getOrigin(),
            'MD5'         => md5_file($dest . '/' . $basename),
        );
        $db->insert(self::TABLE, $bind);
		return true;
    }

    /**
     * @param array $row
     * @param string $documentRoot
     */
    public function load($row, $documentRoot)
    {
        $this->setFileid($row['FILEID']);
        $this->setFormat($row['TYPEANNEX']);
        $this->setName($row['FILENAME']);
        $this->setPath($documentRoot . $row['FILENAME']);
        $this->setComment($row['INFO']);
        $this->setType($row['FILETYPE']);
        if ($this->getType() == Hal_Settings::FILE_TYPE) {
            $this->setDefault($row['MAIN']);
        } else if ($this->getType() == Hal_Settings::FILE_TYPE_ANNEX) {
            $this->setDefaultannex($row['MAIN']);
        }
        $this->setExtension($row['EXTENSION']);
        $this->setDateVisible($row['DATEVISIBLE']);
        $this->setTypeMIME($row['TYPEMIME']);
        $this->setSize($row['SIZE']);
        $this->setOrigin($row['FILESOURCE']);
        $this->setSource($row['SOURCE']);
        $this->setMd5($row['MD5']);
        $this->setImagette($row['IMAGETTE']);
    }

    /**
     * Suppression d'un fichier
     */
    public function deleteFile()
    {
        if (is_file($this->getPath())) {
            unlink($this->getPath());
        }
    }

    /**
     * Indique si un fichier est une vidéo qui pourra être visionnée
     * @return bool
     */
    public function isVideo()
    {
        return in_array($this->getExtension(), ['mp4', 'm4v', 'f4v', 'mov']);
    }

    /**
     * @return bool
     */
    public function isPdf()
    {
        return 'pdf' == $this->getExtension();
    }

    /**
     * @param $filename
     * @return string[]
     */
    static public function get_PDF_info($filename) {
        $return = array();
        $font   = array();
        $cmdPdfinfo = PDFINFO;
        $cmdPdffont = PDFFONTS;
        if (!is_file($filename) || !is_readable($filename)) {
            return [];
        }

        if ( ! is_executable($cmdPdfinfo)) {
            $return['Errors'][] = 'PdfInfo non executable';
        } else {
            $output = [];
            setlocale(LC_CTYPE, "fr_FR.UTF-8"); // escapeshellarg strip les lettres accentuees si on n'est pas dans une locale Utf8
            exec($cmdPdfinfo." ".escapeshellarg($filename), $output);
            foreach ($output as $iline) {
                if (preg_match('/^([a-zA-Z ]+):[[:space:]]*([^\s].*)$/', $iline, $match)) {
                    $key = trim($match[1]);
                    if ( in_array($key, array('Creator', 'Producer', 'Pages', 'Encrypted', 'PDF version')) ) {
                        $return[$key] = trim($match[2]);
                    }
                }
            }
        }
        if (! is_executable($cmdPdffont)) {
            $return['Errors'][] = 'PdfFonts non executable';
        } else {
            $output = [];
            setlocale(LC_CTYPE, "fr_FR.UTF-8"); // escapeshellarg strip les lettres accentuees si on n'est pas dans une locale Utf8
            exec($cmdPdffont. " " . escapeshellarg($filename), $output);
            foreach ($output as $line) {
                $line = preg_split("/\s\s+/", $line);
                if (array_key_exists(1, $line)) {
                    $key = trim($line[1]);
                    if ($key != "" && $key != "type") {
                        $font[$key] = 1; //Récupère seulement la font du pdf
                    }
                }
            }
            $return['Fonts'] = $font;
        }
        return($return);
    }

    /**
     * Retourne la valeur maximum d'un embargo : Date du jour + 2 ans
     * @return string|false a formatted date string
     */
    public function maxEmbargo()
    {
        $site = Hal_Site::getCurrentPortail();
        if ($site) {
            $iniMaxEmbargo = $site->getMaxEmbargo();
            return $iniMaxEmbargo;
        }
        // We never go there, I hope that current portal is always set... but never mind!
        return date('Y-m-d', strtotime('+2 years', strtotime('today UTC')));
    }

    /**
     *
     * @return bool
     */
    public function isEmbargoValid()
    {
        $max = $this->maxEmbargo();
        return  $this->getDateVisible() <= $max;
    }
}
