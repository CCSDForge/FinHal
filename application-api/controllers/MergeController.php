<?php
class MergeController extends Zend_Controller_Action {

    const CODE_NOMODIF = 1;
    const CODE_FUSION = 2;
    const CODE_NEWCOMPTE = 3;
    const CODE_REFUSED = 4;

    /**
     * On supprime les token qui datent de la veille
     */
    public function init()
    {
        Hal_User_Merge::cleanTokens(date("Y-m-d", strtotime("-1 day", strtotime(date('Y-m-d')))));
    }

    /**
     * Requête pour récupérer les informations sur la fusion de 2 comptes utilisateur
     * Prend comme paramètre 2 UID : keeper et merger
     * Retourne
     * code (1 pas de modif, 2 fusion des comptes, 3 création du compte keeper avec merger, 4 fusion interdite)
     * message
     * token (à renvoyer si l'on effectivement faire la fusion de ces 2 comptes)
     */
	public function infoAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $uidToMerge = $this->getRequest()->getParam('merger', 0);
        $uidToKeep = $this->getRequest()->getParam('keeper', 0);
        $authToken = $this->getRequest()->getParam('authToken', '');

        if ($authToken != MERGE_TOKEN) {
            $this->getResponse()->setHttpResponseCode(401);
            echo Zend_Json::encode(['code'=>self::CODE_REFUSED, 'message'=>'Vous n\'êtes pas autorisé à fusionner un compte']);
            return;
        }

        if ($uidToMerge == 0) {
            echo Zend_Json::encode(['code'=>self::CODE_NOMODIF, 'message'=>'Pas d\'uid à fusionner passé en paramètre. Aucune modification à apporter.']);
            return;
        }

        if ($uidToKeep == 0) {
            echo Zend_Json::encode(['code'=>self::CODE_NOMODIF, 'message'=>'Pas d\'uid à conserver passé en paramètre. Aucune modification à apporter.']);
            return;
        }

        $userToMerge = Hal_User::fetchRows(Zend_Db_Table_Abstract::getDefaultAdapter(), Hal_User::TABLE_USER, $uidToMerge);
        if (empty($userToMerge)) {
            echo Zend_Json::encode(['code'=>self::CODE_NOMODIF, 'message'=>'Le compte à fusionner n\'existe pas dans HAL. Aucune modification à apporter.']);
            return;
        }

        // CREER UN TOKEN POUR FAIRE LA FUSION DANS CES 2 CAS SUIVANTS
        $um = Hal_User_Merge::createFromUids($uidToMerge, $uidToKeep);

        $token = $um->createToken();

        $userToKeep = Hal_User::fetchRows(Zend_Db_Table_Abstract::getDefaultAdapter(), Hal_User::TABLE_USER, $uidToKeep);
        if (empty($userToKeep)) {
            echo Zend_Json::encode(['code'=>self::CODE_NEWCOMPTE, 'token'=>$token, 'message'=>'Le compte à conserver n\'existe pas dans HAL. Il va être créé et prendre les paramètres du compte à fusionner.']);
            return;
        }

        // On récupère les tables qui vont être modifiées lors de la fusion.
        $tablesWithUserUID = $um->getValueOccurr('UID', $uidToMerge);

        $message = "";

        foreach ($tablesWithUserUID as $table => $nb) {
            $message .= PHP_EOL . $nb . ' occurences dans la table ' . $table;
        }

        $message .= PHP_EOL . 'Les tables USER, USER_PREF_DEPOT et USER_PREF_MAIL seront mises à jour.';

        echo Zend_Json::encode(['code'=>self::CODE_FUSION, 'token'=>$token, 'message'=>$message]);;
    }

    /**
     * Requête de fusion effective de 2 comptes utilisateur
     * Prend en paramètre le token envoyé lors de la requête d'info
     * Renvoit un message
     */
    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $token = $this->getRequest()->getParam('token', '');
        $um = Hal_User_Merge::createFromToken($token);

        if ($um === null) {
            echo 'Token non reconnu';
            return;
        }

        $tablesWithUserUID = $um->getValueOccurr('UID', $um->getUidFrom());
        $mergeResults = $um->mergeUsers(array_keys($tablesWithUserUID));

        $return ="";

        // Création de la sortie
        foreach ($mergeResults as $table => $res) {
            if (array_key_exists('ok',$res) && $res['ok'] != 0) {
                $return .= 'La table '.$table.' a été modifié avec succès'.PHP_EOL;
            } else if (array_key_exists('error',$res) && ($table == Hal_Cv_Visite::COUNTER || $table == Hal_Document_Visite::COUNTER)) {
                $return .= 'Erreur non fatale(!) en modifiant la table '.$table.PHP_EOL;
            } else if (array_key_exists('error',$res)) {
                $return .= 'Erreur en modifiant la table '.$table.PHP_EOL;
                $this->getResponse()->setHttpResponseCode(500);
                echo $return;
                return;
            }
        }

        // Problème quand l'utilisateur n'a pas de préférences de dépôt ou de mail quand on utilise createUser !
        $userToKeep = new Hal_User();
        $userToKeep->find($um->getUidTo());

        $userToKeep->postModifyUser(); // effacement cache de document + reindexation de documents, ...

        $flashMessenger = $um -> replaceUserProfile();
        echo $return . PHP_EOL . $flashMessenger;
    }
}