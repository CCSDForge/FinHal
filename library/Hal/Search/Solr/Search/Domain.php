<?php

class Hal_Search_Solr_Search_Domain extends Hal_Search_Solr_Search
{
    /**
     * Retourne un tableau des domaines
     * @param string $displayType
     * @param null $typeFilter
     * @return array
     */
    public static function getDomainConsultationArray($displayType = null, $typeFilter = null)
    {
        $rootLevel = '0';
        $portalDomains = [];

        if ($displayType == 'portal') {
            $portalDomains = self::getPortalDomainsAsFacetPrefix();

            // On trie par niveau
            ksort($portalDomains);

            // On prend le niveau racine
            $rootLevel = array_keys($portalDomains)[0][0];
        }

        // DomArray doit ressembler à ça
        // 0 => ['domainCode' => 0.shs, 'domainName' => Sciences Humaines et Sociales, 'domainDisplay' => <a href=''>Sciences ...</a><small>(Nombre de publications correspondantes)</small>]
        // 1 => ['domainCode' => 1.shs.lang, 'domainName' => Langues, 'domainDisplay' => <a href=''>Langues ...</a><small>(Nombre de publications correspondantes)</small>]
        // 2 => ['domainCode' => 0.sdv, 'domainName' => Sciences du Vivant, 'domainDisplay' => <a href=''>Sciences ...</a><small>(Nombre de publications correspondantes)</small>]
        return self::fillDomainConsultationArray([], '', $rootLevel, $portalDomains, $typeFilter);

    }

    /**
     * Dans un résultat de facette, supprime les domaines qui n'appartiennent pas au portail
     *
     * @param array $portalDomains
     * @param array $facetDomains
     * @return array
     */
    public static function removeDomainsNotInPortal($portalDomains, $facetDomains)
    {
        foreach ($facetDomains as $dom => $domCount) {
            if (!array_key_exists($dom, $portalDomains)) {
                unset($facetDomains [$dom]);
            }
        }

        return $facetDomains;
    }

    /**
     * Retourne la liste des domaines d'un portail pour servir de préfixe de facettes
     *
     * @return array
     */
    public static function getPortalDomainsAsFacetPrefix()
    {
        $portalDomains = [];
        $dom = json_decode(file_get_contents(Hal_Settings::getDomains()), true);
        foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($dom), RecursiveIteratorIterator::SELF_FIRST) as $key => $value) {
            $kVal = explode('.', $key);
            $kLevel = count($kVal) - 1; // -1 car commence à niveau 0
            $portalDomains [$kLevel . '.' . $key] = null;
        }
        return $portalDomains;
    }

    /**
     * @param string $domain
     * @param string $count
     * @return array
     * @throws Zend_Exception
     */
    public static function formatDomainForDomainConsultation($domain, $count)
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $translatedDomain = $translate->translate('domain_' . $domain);

        $h = new Hal_View_Helper_Url ();

        $domainDisplayUrl = $h->url([
            'controller' => 'search',
            'action' => 'index',
            'q' => '*',
            'domain_t' => $domain
        ]);

        $domainDisplay = '<a href="' . $domainDisplayUrl . '">' . $translatedDomain . '</a>';
        if ($count !== '') {
            $domainDisplay .= '&nbsp;<small>(' . $count . ')</small>';
        }

        return ['domainCode' => $domain, 'domainName' => $translatedDomain, 'domainDisplay' => $domainDisplay];
    }

    /**
     * @param string $dom
     * @param array $domArray
     * @return array
     */
    public static function addNeededParents($dom, $domArray)
    {
        $parentDomain = self::needParent($dom, $domArray);
        if ($parentDomain !== '') {
            // On ajoute le grand-parent
            $domArray = self::addNeededParents($parentDomain, $domArray);

            // On ajoute le parent
            $domArray[] = Hal_Search_Solr_Search_Domain::formatDomainForDomainConsultation($parentDomain, '');
        }

        return $domArray;
    }

    /**
     * @param $dom
     * @param $domArray
     * @return string
     */
    public static function needParent($dom, $domArray)
    {

        $res = explode('.', $dom);

        // On est dans le cas d'un sous-domaine
        if (!empty($res) && reset($res) !== $dom) {

            $parentDomain = str_replace('.' . end($res), '', $dom);
            $lastDomainEntered = end($domArray)["domainCode"];

            if (!empty($domArray) && strpos($lastDomainEntered, $parentDomain) !== false) {
                // L'element précédent est un parent ou un frère donc pas besoin d'ajouter un parent
                return '';
            } else {
                return $parentDomain;
            }
        }

        return '';
    }

    /**
     * @param $domArray
     * @param $parentDom
     * @param $level
     * @param $portalDomains
     * @param null $typeFilter
     * @return array
     */
    public static function fillDomainConsultationArray($domArray, $parentDom, $level, $portalDomains, $typeFilter = null)
    {
        // Décompte des publications par domaines du niveau $level.'.'.$parentDom
        $doms = Hal_Search_Solr_Search::getFacetField('domain_s', $level . '.' . $parentDom, 'count', $typeFilter);
        if (!is_array($doms)) {
            $doms=[];
        }
        if (!empty($portalDomains)) {
            // Permet d'avoir le décompte des publications par domaine du portail
            $doms = Hal_Search_Solr_Search_Domain::removeDomainsNotInPortal($portalDomains, $doms);
        }

        foreach ($doms as $dom => $count) {

            $dom = substr_replace($dom, '', 0, 2); // On vire le numéro hiérarchique  0. ou 1. (peut probable mais si on a une hiérarchie à 2 chiffres, ça se passe mal)

            // On ajoute les parents s'ils sont inexistants (shs à shs.lang par exemple)
            $domArray = Hal_Search_Solr_Search_Domain::addNeededParents($dom, $domArray);

            $domArray[] = Hal_Search_Solr_Search_Domain::formatDomainForDomainConsultation($dom, $count);

            // On ajoute les sous-domaines
            $domArray = self::fillDomainConsultationArray($domArray, $dom, $level + 1, $portalDomains, $typeFilter);
        }


        return $domArray;
    }
}
