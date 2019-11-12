<?php

/**
 * Class Hal_Arxiv
 * This class manipulate the Arxiv domain from Table REF_DOMAIN_ARXIV
 * It permit to map Hal domains to Arxiv Categories
 *
 * A repenser:
 *    un domHals to domArxiv
 *         avec le premier etant le domaine principal.
 *    un domArxiv to rootArxiv
 *    exist qui rends une chaine ou null
 *    arxivDomains2labels
 *
 *  Et certainement une Classe ArxivDomain
 *        code, libelle en divers langues...
 *  Mais seulement quand nous aurons aussi une classe Hal\Domain
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


    /** New core function for Arxiv
     --------------------------------
     */

     /**
     * @param string[] $haldomains
     * @return string[]
     */
    static public function haldomaines2arxivsubjects($haldomains) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from('REF_DOMAIN_ARXIV')->where('CODE IN (?)', $haldomains);
        $res = [];
        foreach ($db->fetchAll($sql) as $row) {
            $res[$row['CODE']] = $row['ARXIV'];
        }
        return $res;
    }

    /**
     * Pour le document, retourne l'archive Arxiv dans laquelle doit etre deposee l'article
     * Il s'agit de la racine du domaine Arxiv correspondant au premier domaine du document
     * qui a une correspondance dans Arxiv
     *
     * @param Hal_Document $document
     * @return string|null
     */
    static public function document2archive($document) {
        if (ENV_PROD == APPLICATION_ENV) {
            $arxivSubs = self::haldomaines2arxivsubjects($document->getDomains());

            foreach ($arxivSubs as $subject) {
                /** take the first One in array */
                return self::subject2archive($subject);
            }
            return null;
        } else {
            return 'test';
        }
    }

    /**
     * For a subject arXiv, return the name of archive (root name)
     * which corresponds to the sword collection on arXiv
     * @param string $subject
     * @return string
     */
    static public function subject2archive($subject) {
        $index = strpos($subject, '.');
        if ($index === false) {
            $arxivCode = $subject;
        } else {
            $arxivCode = substr($subject, 0, $index);
        }
        /** Les primary categorie sont peu nombreuses:
         * cs  econ eess math physics q-bio q-fin stat
         * @see http://arxitics.com/help/categories
         */
        if ($arxivCode == 'math-ph') {
            $arxivCode = 'physics';
        }
        $match = [];
        if (preg_match('/(cs|econ|eess|math|physics|q-bio|q-fin|stat)/', $arxivCode, $match)) {
            $arxivCode = $match[1];
        } else {
            $arxivCode = 'physics';
        }
        return $arxivCode;
    }

    /**
     * Return an array code_arxiv => English label of arXiv subject
     * @param string[] $domains get from @see haldomaines2arxivsubjects
     * @return string[]
     * @throws Zend_Exception
     */
    static public function arxiv2labels($domains) {
        $categories = [];
        /** @var Zend_Translate_Adapter $translator */
        $translator = Zend_Registry::get('Zend_Translate');
        foreach ($domains as $haldomain => $arxivCode) {
            if ($arxivCode) {
                $categories[$arxivCode] = $translator ->translate('domain_' . $haldomain, 'en');
            }
        }
        return $categories;
    }

    /** END of new Core function */

    /** OLD ones */
    /**
     * @deprecated
     * Equivalence de code arxiv
     * Certains domaines Arxiv ont change, on map les anciens vers les nouveaux
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
     * @deprecated
     * Retourne le premier code de domaine Hal ayant un correspondant Arxiv
     * Ne retourne pas le code arXiv correspondant
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
            return $code;
        }
        return '';
    }

    /**
     * @deprecated
     * Retourne la collection Arxiv correspondant au domain Hal
     * La collection corresponds au code Arxiv du domain racine Hal
     *
     * TODO: BM: ??? La collection devrait correspondre a la racine du domaine Arxiv, pas a la traduction du domaine HAL
     *     Cela cree un BUG lorsque le sous domaine n'est pas dans la meme categorie par rapport a Arxiv.
     *     Cela demande la fonction transformArxivCode qui ne sait que mal traiter les choses...
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
     * @deprecated
     * Retourne le tableau code_arxiv => Libelle anglais du domaine arxiv
     * @param string[] $domains sdv.bbm.bs
     * @return string[]
     * @throws Zend_Exception
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
     * @deprecated
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
                // Certains domaines Arxiv on change, on maps les anciens vers les nouveaux
                $dom = self::transformArxivCode($dom);
                return self::domain2collection($dom);
            }
        } else {
            // Plateforme autre que production!!!
            return "test";
        }
        return '';
    }

    /**
     * @deprecated : prendre la liste et regarder si c'est vide
     *
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
     * @deprecated : faire une requete SQL pour rendre juste un bool... pas top
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