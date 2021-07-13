<?php

/**
 * Class Hal_Arxiv
 * This class manipulate the Arxiv domain from Table REF_DOMAIN_ARXIV
 * It permit to map Hal domains to Arxiv Categories
 */
class Hal_Arxiv
{
    const MAX_RECURSION = 51;
    const TABLE = 'DOC_IDARXIV';

    const ERROR_RESUME = 'resume';
    const ERROR_NOSOURCE = 'filenosource';
    const ERROR_FILESIZE = 'filesize';
    const ERROR_NOBBL = 'nobbl';
    const ERROR_DOMAIN = 'domain';

    /**
     * Domaines arXiv
     */
    const TABLE_DOMAIN_ARXIV = 'REF_DOMAIN_ARXIV';

    /**
     * Equivalence de code arxiv
     * Certains domaines Arxiv on change, on maps les anciens vers les nouveaux
     * @param string $code
     * @return string
     */
    static public function transformArxivCode($code) {
        switch ($code) {
            case 'math.math-mp':
                return 'phys.mphy';
            case 'info.info-bi':
                return 'sdv.bibs';
            default:
                return $code;
        }
    }

    /**
     * Retourne le premier code de domaine Hal ayant un correspondant Arxiv
     *
     * @param Hal_Document $document
     * @return string
     */
    static public function getMainArXivDomain($document)
    {
        if ($document->getDocid() != 0) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $code = '';
            foreach ($document->getDomains() as $dom) {
                $sql = $db->select()->from('REF_DOMAIN_ARXIV', 'CODE')->where('CODE = ?', $dom);
                $res = $db->fetchOne($sql);
                if ($res) {
                    $code = $res;
                    break;
                }
            }
            // $code est toujours une chaine, vide peut etre
            // Certains domaines Arxiv on change, on maps les anciens vers les nouveaux
            return self::transformArxivCode($code);
        }
        return '';
    }

    /**
     * Retourne la collection Arxiv correspondant au domain Hal
     * La collection corresponds au code Arxiv du domain racine Hal
     * @param string $dom
     * @return string
     */
    static public function domain2collection($dom) {
        if (strpos($dom, '.') === false) {
            $arxivCode = $dom;
        } else {
            $arxivCode = substr($dom, 0, strpos($dom, '.'));
            }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_DOMAIN_ARXIV, 'ARXIV')->where('CODE = ?', $arxivCode);
        return $db->fetchOne($sql);
    }

    /**
     * Retourne le tableau code_arxiv => Libelle anglais du domaine arxiv
     * @param string[] $domains sdv.bbm.bs
     * @return string[]
     */
    static public function getDomains2ArxivCategories($domains) {
        $categories = [];
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        /** @var Zend_Translate_Adapter $translator */
        $translator = Zend_Registry::get('Zend_Translate');
        foreach ($domains as $dom) {
                $dom = self::transformArxivCode($dom);
                $sql = $db->select()->from(self::TABLE_DOMAIN_ARXIV, 'ARXIV')->where('CODE = ?', $dom);
                $code = $db->fetchOne($sql);
                if ($code) {
                    $categories[$code] = $translator ->translate('domain_' . $dom, 'en');
                }
        }
        return $categories;
    }

    /**
     * Renvois la collection Arxiv (Partie racine du domaine Arxiv) du document
     * Le domaine Arxiv choisit est le premier de la liste
     *
     * Si pas environnement de production, alors retourne test
     * @param Hal_Document $document
     * @return string
     */
    static public function getArXivSwordCollection($document)
    {
        if (ENV_PROD == APPLICATION_ENV) {
            if ($document->getDocid() != 0) {
                $dom = self::getMainArXivDomain($document);
                return self::domain2collection($dom);
            }
        } else {
            // Plateforme autre que production!!!
            return "test";
        }
        return '';
    }

    /**
     * Indique s'il existe des domaines arXiv pour les domaines d'un document
     * @param mixed $domainHal
     * @return bool
     */
    public static function domainArxivOk($domainHal)
    {
        if (empty($domainHal)) {
            return false;
        }

        if (! is_array($domainHal)) {
            $domainHal = array($domainHal);
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_DOMAIN_ARXIV, 'COUNT(*)')
            ->where('ARXIV LIKE ?', '%.%')
            ->where('CODE IN (?)', $domainHal);
        return $db->fetchOne($sql) > 0;
    }

    /**
     * Indique s'il existe des domaines arXiv pour les domaines d'un document
     * @param mixed $domainHal
     * @return bool
     */
    public static function domainArxivExist($domainHal)
    {
        if (empty($domainHal)) {
            return false;
        }

        if (!is_array($domainHal)) {
            $domainHal = array($domainHal);
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_DOMAIN_ARXIV, 'COUNT(*)')
            ->where('CODE IN (?)', $domainHal);
        return $db->fetchOne($sql) > 0;
    }

}