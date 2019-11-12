<?php

/**
 * PhotoController
 *
 * @author
 * @version
 */
class PhotoController extends Zend_Controller_Action
{

    /**
     *
     * @var string chemin vers l'image par défaut
     */
    const DEFAULT_IMG_PATH = '/../public/img/user.png';

    public function indexAction ()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $uid = $this->_getParam('uid', 0);

        $uid = intval($uid);

        if ($uid == 0) {
            $photoPathName = APPLICATION_PATH . self::DEFAULT_IMG_PATH;
            return;
        }

        $user = new Ccsd_User_Models_User(array(
                'uid' => $uid
        ));
        $photoPathName = $user->getPhotoPathName($this->_getParam('size'));

        if ($photoPathName != false) {
        } else {
            $photoPathName = APPLICATION_PATH . self::DEFAULT_IMG_PATH;
        }



        $mimeType = 'image/jpg';
//         $modifiedTime = filemtime($photoPathName);
        $size =  filesize($photoPathName);
        $data = file_get_contents($photoPathName);


        $maxAge = 3600;

        $expires = gmdate('D, d M Y H:i:s \G\M\T', time() + $maxAge);

        $this->getResponse()
        //->setHeader('Last-Modified', $modifiedTime, $replace = true)
        //->setHeader('ETag', md5($modifiedTime), $replace = true)
        ->setHeader('Expires', $expires, true)
        ->setHeader('Pragma', '',  true)
        ->setHeader('Cache-Control', 'max-age=' . $maxAge, true)
        ->setHeader('Content-Type', $mimeType, true)
        ->setHeader('Content-Length', $size, true)
        ->setBody($data);


    }
}
