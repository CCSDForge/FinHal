<?php

/**
 * Class CracController
 * A la CRAC query
 */
class CracController extends Zend_Controller_Action
{

    const VALID_TYPES = [self::CRAC_TYPE_PEER_TRUE, self::CRAC_TYPE_PEER_FALSE, self::CRAC_TYPE_BOOKS];


    const API_ENDPOINT = 'https://api.archives-ouvertes.fr/search/';
    const THE_CRACURL = '/crac';
    const CRAC_TYPE_PEER_TRUE = 'peer-true';
    const CRAC_TYPE_PEER_FALSE = 'peer-false';
    const CRAC_TYPE_BOOKS = 'books';

    public function indexAction()
    {
        $this->view->layout()->pageTitle = 'CRAC';
        echo  $this->view->render('crac/help.phtml');
    }

    public function searchAction()
    {
        $this->view->layout()->pageTitle = 'CRAC - Résultats. Tri par date, puis titres';
        $error = false;
        $first = trim($this->getRequest()->getParam('first'));
        $last = trim($this->getRequest()->getParam('last'));
        $type = $this->getRequest()->getParam('type', ['sillyBogusType']);
        $queryType = $type[0];

        if (strlen($first) > 100 || strlen($first) < 1) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Erreur: Taille du prénom invalide.');
            $error = true;
        }
        if (strlen($last) > 100 || strlen($last) < 2) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Erreur: Taille du nom invalide.');
            $error = true;
        }

        if (!in_array($queryType, self::VALID_TYPES)) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Erreur: Type invalide.');
            $error = true;
        }

        if ($error) {
            $this->redirect(self::THE_CRACURL);
        }

        $queryString = $this->cracLikeQueryAssemble($last, $first, $queryType);
        try {
            $apiResults = $this->cracLikeQuery($queryString);
        } catch (Exception $exception) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Echec de la requête API : ' . $exception->getMessage());
            $error = true;
        }

        if ($error) {
            $this->redirect(self::THE_CRACURL);
        }

        $resultsArray = json_decode($apiResults, true);

        if ($resultsArray == null) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Erreur lors du traitement de la réponse.');
            $this->redirect(self::THE_CRACURL);
        }

        $this->view->queryString = $queryString;
        $this->view->results = $resultsArray['response']['docs'];
        $this->view->numFound = $resultsArray['response']['numFound'];
        $this->view->first = $first;
        $this->view->last = $last;
        $this->view->type = $queryType;


    }

    /**
     * @param string $last
     * @param string $first
     * @param string $queryType
     * @return string
     */
    private static function cracLikeQueryAssemble(string $last, string $first, string $queryType): string
    {
        $authorQuery = self::getCracAuthorQuery($last, $first);
        $commonDateFilter = self::getCracDateFilterQuery();
        $typeFilter = self::getCracTypeFilterQuery($queryType);
        $queryString = '' . self::API_ENDPOINT . '?q=' . $authorQuery . '&fq=' . $commonDateFilter . '&fq=' . $typeFilter;
        $queryString .= '&wt=json&fl=label_s,docType_s,submitType_s,halId_s,version_i,producedDateY_i&rows=400';
        return $queryString . '&sort=producedDate_tdate+desc,title_sort+asc';


    }

    /**
     * @param string $last
     * @param string $first
     * @return string
     */
    private static function getCracAuthorQuery(string $last, string $first): string
    {
        $lastName = str_replace(' ', '?', $last);
        // $first[0] could be enough
        $firstNameLetter = mb_substr($first, 0, 1, 'utf-8');
        $fullName = $firstNameLetter . '*' . $lastName;
        return 'authLastName_sci:(' . urlencode($lastName) . ')' . urlencode(' AND ') . 'authFullName_sci:(' . urlencode($fullName) . ')';
    }

    /**
     * @return string
     */
    private static function getCracDateFilterQuery(): string
    {
        return rawurlencode('producedDateY_i:[2018 TO 2019]');

    }

    /**
     * @param string $queryType
     * @return string
     */
    private static function getCracTypeFilterQuery(string $queryType): string
    {
        switch ($queryType) {

            case self::CRAC_TYPE_PEER_TRUE:
                $typeFilter = '(docType_s:ART AND NOT peerReviewing_s:0) OR (docType_s:ART AND inPress_bool:true AND NOT peerReviewing_s:0) OR (docType_s:COMM AND proceedings_s:1 AND NOT peerReviewing_s:0)';
                break;

            case self::CRAC_TYPE_PEER_FALSE:
                $typeFilter = '(docType_s:ART AND  peerReviewing_s:0) OR (docType_s:ART AND  inPress_bool:true AND peerReviewing_s:0) OR (docType_s:COMM AND proceedings_s:1 AND peerReviewing_s:0)';
                break;

            case self::CRAC_TYPE_BOOKS:
                $typeFilter = '(docType_s:OUV OR docType_s: COUV OR docType_s:DOUV) OR (docType_s:OUV AND inPress_bool:true)';
                break;

            default:
                throw new UnexpectedValueException('Invalid Type Value');
                break;

        }
        return rawurlencode($typeFilter);
    }

    /**
     * @param string $cracQuery
     * @return string
     * @throws Exception
     */
    private static function cracLikeQuery(string $cracQuery)
    {

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_USERAGENT, 'release the cracurl');
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 33); // timeout in seconds
        curl_setopt($curlHandler, CURLOPT_URL, $cracQuery);
        curl_setopt($curlHandler, CURLOPT_TIMEOUT, 66); // timeout in seconds


        $info = curl_exec($curlHandler);

        if (curl_errno($curlHandler) == CURLE_OK) {
            return $info;
        } else {
            $errno = curl_errno($curlHandler);
            $error_message = curl_strerror($errno) . '. Query: ' . $cracQuery;
            curl_close($curlHandler);
            throw new Exception("Erreur ({$errno}): {$error_message}", $errno);
        }
    }
}