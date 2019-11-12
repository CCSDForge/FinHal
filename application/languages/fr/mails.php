<?php
return array(

    'account_create_tpl_name'					=>	'Création de compte',
    'account_create_mail_subject'				=>	'Activation de votre compte',

    'account_lost_login_tpl_name'				=>	'Identifiant oublié',
    'account_lost_login_mail_subject'			=>	'Liste de vos identifiants',

    'account_lost_pwd_tpl_name'					=>	'Mot de passe oublié',
    'account_lost_pwd_mail_subject'				=>	'Mot de passe oublié',

    'document_submitted_tpl_name'				=>	'Nouveau dépôt',
    'document_submitted_mail_subject'			=>	'Nouveau dépôt reçu',

    'document_submitted_online_tpl_name'		=>	'Nouveau dépôt mis en ligne',
    'document_submitted_online_mail_subject'	=>	'Nouveau dépôt mis en ligne',

    'document_accepted_tpl_name'				=>	'Document accepté',
    'document_accepted_mail_subject'			=>	'Document en ligne',

    'moderation_tpl_name'						=>	'Demande de modération',
    'moderation_mail_subject'					=>	'Demande de modération',

    'notice_submitted_tpl_name'					=>	'Nouvelle notice',
    'notice_submitted_mail_subject'				=>	'Notice déposée',



    'account_create'                =>  '[%%PORTAIL%%] confirmation de votre inscription',
    'account_lost_pwd'              =>  '[%%PORTAIL%%] Confirmation de réinitialisation du mot de passe',
    'account_lost_login'            =>  '[%%PORTAIL%%] Récupération du login',

    //Dépôt
    'notice_submitted'                      =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Votre nouvelle référence bibliographique',
    'document_submitted'                    =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Votre nouveau dépôt',
    'document_submitted_online'             =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Votre nouveau dépôt',
    'document_accepted'                     =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Dépôt accepté',
    'document_accepted_arxiv'               =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Dépôt accepté',
    'document_toupdate'                     =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Dépôt à modifier',
	'document_toupdate_reminder'            =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Relance : dépôt à modifier',
    'document_refused'                      =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Dépôt non accepté',
    'document_deleted'                      =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Dépôt non accepté',
    'alert_moderator_new_submission'        =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Un nouveau dépôt',
    'document_adminmodify'        =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : De nouveau en modération',

    'alert_validator_new_validation'		=>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Un nouveau dépôt à expertiser',
	'alert_validator_new_validation_reminder'=>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Relance : un nouveau dépôt à expertiser',
	'alert_validator_end_validation'		=>	"[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Fin de l'expertise du document",
	'alert_validator_confirm_validation'	=>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Votre expertise est prise en compte',		

	'alert_corresp_author_document_accepted'=>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Un papier en ligne',
    'alert_user_search'						=>  '[%%PORTAIL%%] %%ALERT_NUM_DOCS%% nouveau(x) document(s) correspondant à votre abonnement',

    'document_claim_ownership'          =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Demande de partage de propriété',
    'document_claim_ownership_direct'       =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Partage de propriété',
    'document_claim_ownership_ok'       =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Demande de partage de propriété acceptée',
    'document_claim_ownership_ko'       =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Demande de partage de propriété refusée',

    'document_file_access'       =>  "[%%PORTAIL%%] %%DOC_ID%% : Demande d'accès au fichier en accès restreint",
    'document_file_access_ok'       =>  "[%%PORTAIL%%] %%DOC_ID%% : Demande d'accès au fichier ACCEPTÉ",
    'document_file_access_ko'       =>  "[%%PORTAIL%%] %%DOC_ID%% : Demande d'accès au fichier REFUSÉ",

    'ref_structure_ajout'       =>  "[%%PORTAIL%%] Ajout d'une sous-structure",
    'ref_structure_modification'       =>  "[%%PORTAIL%%] Modification d'une structure",
    'ref_structure_fusion'       =>  "[%%PORTAIL%%] Fusion de structures",
    'ref_structure_suppression'       =>  "[%%PORTAIL%%] Suppression d'une structure",

    'document_alert_ownership'              =>  '[%%PORTAIL%%][%%DOC_FORMAT%%] %%DOC_ID%% : Etes-vous auteur de ce document ?',
    'document_alert_refstruct'              => '[%%STRUCTNAME%%][%%DOC_FORMAT%%] Nouveau document en ligne affilié à la structure',
    'document_alert_admin'              => '[%%PORTAIL%%][%%DOC_FORMAT%%] Nouveau document en ligne dans le portail',
);