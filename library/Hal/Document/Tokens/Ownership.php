<?php

/**
 * Gestion des tokens pour les document du CCSD
 * @author sdenoux
 *
 */
class Hal_Document_Tokens_Ownership
{
    const TOKEN_STRING_LENGTH = 40;
    /**
     * UID de l'utilisateur
     *
     * @var int
     */
    protected $_uid;
    /**
     * UID du document l'utilisateur
     *
     * @var string
     */
    protected $_docid;
    /**
     * Token disponible pour l'utilisateur
     *
     * @var string
     */
    protected $_token;
    /**
     * Date de dernière modification du Token
     *
     * @var string timestamp
     */
    protected $_time_modified;
    /**
     * Usage pour lequel le token a été créé
     *
     * @var string
     */
    protected $_usage;
    /**
     * Hal_Document_Tokens_Ownership constructor.
     * @param array|null $options
     */
    public function __construct (array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }
    /**
     * @param array $options
     * @return Hal_Document_Tokens_Ownership
     */
    public function setOptions (array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $key = strtolower($key); // les noms de champs sont en majuscules dans la BDD
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Génère un jeton unique qui sert
     * - 1 pour trouver le compte à activer ;
     * - 2 pour réinitialiser le mot de passe
     */
    public function generateUserToken ($type = 'VALID')
    {
        $this->setToken(sha1(time() . uniqid(rand(), true)));
    }
    /**
     * @return int $_uid
     */
    public function getUid ()
    {
        return $this->_uid;
    }
    /**
     *
     * @return int $_docid
     */
    public function getDocid ()
    {
        return $this->_docid;
    }
    /**
     * @return string $_token
     */
    public function getToken ()
    {
        return $this->_token;
    }
    /**
     * @return string $_time_modified
     */
    public function getTime_modified ()
    {
        return ( $this->_time_modified != '' ) ? $this->_time_modified : date('Y-m-d H:i:s');
    }
    /**
     * @param int $_uid
     * @return Hal_Document_Tokens_Ownership
     */
    public function setUid ($_uid)
    {
        if ($_uid == '') {
            $this->_uid = null;
            return $this;
        }

        $this->_uid = filter_var($_uid, FILTER_SANITIZE_NUMBER_INT);

        if ($this->_uid <= 0) {
            throw new InvalidArgumentException(
                    'Le UID utilisateur doit être supérieur à 0.');
        } else {

            return $this;
        }
    }
    /**
     * @param int $docid
     * @return Hal_Document_Tokens_Ownership
     */
    public function setDocid ($docid)
    {
        $this->_docid = $docid;
        return $this;
    }
    /**
     *
     * @param string $_token
     * @return Hal_Document_Tokens_Ownership
     */
    public function setToken ($_token)
    {
        $_token = filter_var($_token, FILTER_SANITIZE_STRING);
        if (strlen($_token) != self::TOKEN_STRING_LENGTH ) {
            throw new InvalidArgumentException("Le jeton n'est pas valide");
        } else {
            $this->_token = $_token;
        }
        return $this;
    }
    /**
     * @param string $_time_modified
     * @return Hal_Document_Tokens_Ownership
     */
    public function setTime_modified ($_time_modified)
    {
        $this->_time_modified = $_time_modified;
        return $this;
    }
    /**
     * @return string $_usage
     */
    public function getUsage ()
    {
        return $this->_usage;
    }
    /**
     * Fixe le type d'usage pour lequel le jeton a été prévu
     *
     * @param string $_usage
     * @return Hal_Document_Tokens_Ownership
     */
    public function setUsage ($_usage)
    {
        $this->_usage = filter_var($_usage, FILTER_SANITIZE_STRING);
        return $this;
    }
}

