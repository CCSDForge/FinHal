<?php
set_time_limit(0);

/**
 * SWORD Controller
 *
*/
class SwordController extends Zend_Controller_Action
{
    private $_uid=null;
    private $_pwd=null;
    private $_site=null;

    const COMP_GROBID = 'grobid';
    const COMP_IDEXT = 'idext';
    const COMP_AFFILIATION = 'affiliation';

    public function init()
    {

        if (Hal_Settings_Features::hasDocSubmit() === false) {
            header('HTTP/1.1 503 Service Unavailable');
            header('Retry-After: 3600'); // plz come back in 1 hour
            exit;
        }

        // authentification HTTP Basic
        $authBasic = new Ccsd_Auth_Check();
        if (! $authBasic->Basic()) {
            header('WWW-Authenticate: Basic realm="SWORD"');
            header('HTTP/1.1 401 Unauthorized');
            exit();
        }

        $mySqlAuthAdapter = new Ccsd_Auth_Adapter_Mysql($authBasic->user, $authBasic->pwd);
        $halUser = new Hal_User();
        $mySqlAuthAdapter->setIdentity($halUser);
        $result = Hal_Auth::getInstance()->authenticate($mySqlAuthAdapter);

        if (Zend_Auth_Result::FAILURE == $result->getCode()) {
            header('WWW-Authenticate: Basic realm="SWORD"');
            header('HTTP/1.1 401 Unauthorized');
            exit();
        }

        $action = $this->getRequest()->getActionName();

        if ( !in_array($action, array('servicedocument', 'upload')) ) {
        	// detection du portail
            $this->_site = Hal_Site::exist($action, Hal_Site::TYPE_PORTAIL, true);
            if ( $this->_site ) {

                // soumission autorisée pour ce site (oui par défaut)
                //$oHalSite = new Hal_Site_Settings_Portail($this->_site);
                $oHalSite = Hal_Site_Settings_Portail::loadFromSite($this->_site);
                if (!$oHalSite->getSubmitAllowed()) {
                    header('WWW-Authenticate: Basic realm="SWORD"');
                    header('HTTP/1.1 401 Unauthorized');
                    exit();
                }

                $this->_site->registerSiteConstants();
                Hal_Site::setCurrentPortail($this->_site);
                Zend_Registry::set('website', $this->_site);
                if ( preg_match('#/([a-z0-9]+[_-][0-9]{8})(v([0-9]+))?$#', $_SERVER['REQUEST_URI'], $match) ) {
                    $this->forward('index', null, null, array('identifiant'=>$match[1], 'version'=>Ccsd_Tools::ifsetor($match[3], 0)));
                } else {
                    $this->forward('index', null, null);
                }
            } else if ( preg_match('/^([a-z0-9]+[_-][0-9]{8})(v([0-9]+))?$/', $action, $match) ) {
                $article = Hal_Document::find(0, $match[1], Ccsd_Tools::ifsetor($match[3],0));
                if ( $article === false ) {
                    $sword = new Hal_Sword_Server(Hal_Auth::getUid());
                    echo $sword->error('ErrorBadRequest', 'Paper Id can not be found');
                    exit;
                }
                $this->_site = Hal_Site::loadSiteFromId($article->getSid());
                $this->_site->registerSiteConstants();
                Hal_Site::setCurrentPortail($this->_site);
                Zend_Registry::set('website', $this->_site);
                $this->forward('index', null, null, array('identifiant'=>$article->getId(), 'version'=>$article->getVersion()));
            } else {
                $this->redirect('/docs/sword/');
                exit;
            }
        }
        $this->_uid = $authBasic->user;
        $this->_pwd = $authBasic->pwd;
    }

    /**
     * handle the SWORD method
     */
    public function indexAction()
    {     
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    	$sword = new Hal_Sword_Server(Hal_Auth::getUid());
        return $sword->handle($this->getRequest(), $this->_site);
    }

    /**
     * get SWORD service document information
     */
    public function servicedocumentAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $sword = new Hal_Sword_Server(Hal_Auth::getUid());
        return $sword->ServiceDocument();
    }

    /**
     * page de test de l'upload
     */
    public function uploadAction()
    {
        $form = new Ccsd_Form();
        $form->setMethod('post');
        $form->setAttrib('enctype', 'multipart/form-data');

        $form->addElement('checkbox', 'ExportToArxiv', array('label'=>'Transférer le dépôt sur arXiv'));
        $form->addElement('checkbox', 'ExportToPMC', array('label'=>'Transférer le dépôt sur PubMed Central'));
        $form->addElement('checkbox', 'HideForRePEc', array('label'=>"Cacher le dépôt de l'export RePEc"));
        $form->addElement('checkbox', 'HideInOAI', array('label'=>"Cacher le dépôt de l'export OAI"));
        $form->addElement('checkbox', 'AllowGrobidCompletion', array('label'=>"Autoriser la complétion d'information par Grobid"));
        $form->addElement('checkbox', 'AllowIdExtCompletion', array('label'=>"Autoriser la complétion d'information par un service externe (arxiv, crossref, etc)"));
        $form->addElement('checkbox', 'AllowAffiliationCompletion', array('label'=>"Autoriser la complétion d'affiliation pour les auteurs"));
        
        $portails = [];
        foreach ( Hal_Site_Portail::getInstances() as $instance ) {
            //$portails[$instance['SITE']] = $instance['NAME'];

            // soumission autorisée pour ce site (oui par défaut)
            $oHalSite = Hal_Site::loadSiteFromId($instance['SID']);
            $oHalSitePortail = Hal_Site_Settings_Portail::loadFromSite($oHalSite);
            $bSubmitAllowed = $oHalSitePortail->getSubmitAllowed();
            if (!isset($bSubmitAllowed) || $bSubmitAllowed) {
                $portails[$instance['SITE']] = $instance['NAME'];
            }

        }
        $form->addElement('select', 'portail', array('label'=>"Portail", 'multiOptions'=>$portails));
        $form->setDefault('portail', 'hal');
        $form->addElement('text', 'documentId', array('label'=>"Identifiant du document hal (pour modification ou nouvelle version)"));
        $form->addElement('select', 'packaging', array('label' => 'Format du fichier transféré', 'multiOptions'=>array('http://purl.org/net/sword-types/AOfr'=>'AOfr', 'http://jats.nlm.nih.gov/publishing/tag-library/'=>'JATS')));
        $form->addElement('select', 'format', array('label' => 'Type du fichier transféré', 'multiOptions'=>array('text/xml'=>'XML', 'application/zip'=>'ZIP')));
        $form->addElement('text', 'fileName', array('label'=>'Nom du fichier xml (si format zip)'));
        $form->addElement('file', 'swordFile', array('label'=>'Fichier', 'required'=>true));
        $form->addElement('submit', 'submit', array('label'=>'Transférer', 'class'=>'btn btn-primary'));

        if ( $this->getRequest()->isPost() ) {
            $params = $this->getRequest()->getPost();

            if ( $form->isValid($params) ) {
                if ( !($params['format'] == 'application/zip' && $params['fileName'] == '') ) {
                    if ( isset($params['portail']) ) {
                        if ($params['documentId'] != '') {
                            // Le portail n'a pas a etre indique en cas de PUT
                            $curl = curl_init(SWORD_API_URL . '/'. $params['documentId']);
                            curl_setopt($curl, CURLOPT_PUT, true);
                        } else {
                            $curl = curl_init(SWORD_API_URL . '/'.$params['portail'].'/');
                            curl_setopt($curl, CURLOPT_POST, true);
                        }
                        curl_setopt($curl, CURLOPT_HEADER, false);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
                        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
                        $headers = array('Packaging: ' . $params['packaging'], 'Content-Type: ' . $params['format']);
                        if ($params['documentId'] == '') {
                            $headers[] = 'Content-MD5: ' . md5_file($_FILES['swordFile']['tmp_name']);
                        }
                        if ($params['format'] == 'application/zip') {
                            $headers[] = 'Content-Disposition: attachment; filename=' . $params['fileName'];
                        }
                        if (isset($params['ExportToArxiv']) && $params['ExportToArxiv'] == 1) {
                            $headers[] = 'Export-To-Arxiv: true';
                        }
                        if (isset($params['ExportToPMC']) && $params['ExportToPMC'] == 1) {
                            $headers[] = 'Export-To-PMC: true';
                        }
                        if (isset($params['HideForRePEc']) && $params['HideForRePEc'] == 1) {
                            $headers[] = 'Hide-For-RePEc: true';
                        }
                        if (isset($params['HideInOAI']) && $params['HideInOAI'] == 1) {
                            $headers[] = 'Hide-In-OAI: true';
                        }
                        if (isset($params['AllowGrobidCompletion']) && $params['AllowGrobidCompletion'] == 1) {
                            $headers[] = 'X-Allow-Completion: ' . self::COMP_GROBID;
                        }
                        if (isset($params['AllowIdExtCompletion']) && $params['AllowIdExtCompletion'] == 1) {
                            $headers[] = 'X-Allow-Completion: ' . self::COMP_IDEXT;
                        }
                        if (isset($params['AllowAffiliationCompletion']) && $params['AllowAffiliationCompletion'] == 1) {
                            $headers[] = 'X-Allow-Completion: ' . self::COMP_AFFILIATION;
                        }
                        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($curl, CURLOPT_USERPWD, $this->_uid . ':' . $this->_pwd);

                        if ($params['documentId'] != '') {
                            $content = file_get_contents($_FILES['swordFile']['tmp_name']);
                            $putData = tmpfile();
                            fwrite($putData, $content);
                            fseek($putData, 0);
                            curl_setopt($curl, CURLOPT_INFILE, $putData);
                            curl_setopt($curl, CURLOPT_INFILESIZE, strlen($content));
                        } else {
                            curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($_FILES['swordFile']['tmp_name']));
                        }
                        
                        $return = curl_exec($curl);
                        
                        $this->view->return = $return;
                        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        try {
                            $entry = @new SimpleXMLElement($return);
                            $entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                            $entry->registerXPathNamespace('sword', 'http://purl.org/net/sword/');
                            if (in_array($code, array(200, 201, 202, 302))) {
                                $this->view->identifiant = $entry->id;
                                $this->view->url = $entry->link['href'];
                            } else {
                                $this->view->error = (string)$entry->xpath('/sword:error/sword:verboseDescription')[0];
                            }
                        } catch (Exception $e) {
                            $this->view->error = $code . ': ' . $e->getMessage();
                        }
                    }
                }
            } else {
                $form->populate($params);
            }
        }
        $this->view->form = $form;
    }

}