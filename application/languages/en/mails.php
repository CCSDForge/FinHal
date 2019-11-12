<?php
return array(

    'account_create_tpl_name'					=>	'Account creation',
    'account_create_mail_subject'				=>	'Activation of your account',

    'account_lost_login_tpl_name'				=>	'Forgotten login',
    'account_lost_login_mail_subject'			=>	'List of your logins',

    'account_lost_pwd_tpl_name'					=>	'Forgotten password',
    'account_lost_pwd_mail_subject'				=>	'Forgotten password',

    'document_submitted_tpl_name'				=>	'New submission',
    'document_submitted_mail_subject'			=>	'New received submission',

    'document_submitted_online_tpl_name'		=>	'New online submission',
    'document_submitted_online_mail_subject'	=>	'New online submission',

    'document_accepted_tpl_name'				=>	'Document accepted',
    'document_accepted_mail_subject'			=>	'Document online',

    'moderation_tpl_name'						=>	'Moderation request',
    'moderation_mail_subject'					=>	'Moderation request',

    'notice_submitted_tpl_name'					=>	'New record',
    'notice_submitted_mail_subject'				=>	'Record submitted',



    'account_create'                =>  '[%%PORTAIL%%] confirmation of your registration',
    'account_lost_pwd'              =>  '[%%PORTAIL%%] Confirmation of your password reset',
    'account_lost_login'            =>  '[%%PORTAIL%%] Login recovery',

    //Dépôt
    'notice_submitted'                      =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Your new bibliographical reference',
    'document_submitted'                    =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Your new submission',
    'document_submitted_online'             =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Your new submission',
    'document_accepted'                     =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Accepted submission',
    'document_accepted_arxiv'               =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Accepted submission',
    'document_toupdate'                     =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Submission to be modified',
    'document_toupdate_reminder'            =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Reminder : Submission to be modified',
    'document_refused'                      =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Submission not accepted',
    'document_deleted'                      =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Submission not accepted',
    'alert_moderator_new_submission'        =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : A new submission',
    'document_adminmodify'        =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Back to moderation',

    'alert_validator_new_validation'		=>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : A new submission to be evaluated',
    'alert_validator_new_validation_reminder'=>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Reminder : a new submission to be evaluated',
    'alert_validator_end_validation'		=>	"[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : End of the document evaluation",
    'alert_validator_confirm_validation'	=>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Your expertise has been taken into account',

    'alert_corresp_author_document_accepted'=>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Online paper',
    'alert_user_search'						=>  '[%%PORTAIL%%] %%ALERT_NUM_DOCS%% new document(s) corresponding to you subscription',

    'document_claim_ownership'          =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Request of property share',
    'document_claim_ownership_direct'       =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Property shared',
    'document_claim_ownership_ok'       =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Request of property share accepted',
    'document_claim_ownership_ko'       =>  '[%%PORTAIL%%] %%DOC_ID%%, version %%DOC_VERSION%% : Request of property share denied',

    'document_file_access'       =>  "[%%PORTAIL%%] %%DOC_ID%%: Access request to the document",
    'document_file_access_ok'       =>  "[%%PORTAIL%%] %%DOC_ID%%: Access GRANTED to the document",
    'document_file_access_ko'       =>  "[%%PORTAIL%%] %%DOC_ID%%: Access DENIED to the document",
    
    'ref_structure_ajout'       =>  "[%%PORTAIL%%] Research structure added",
    'ref_structure_modification'       =>  "[%%PORTAIL%%] Research structure modified",
    'ref_structure_fusion'       =>  "[%%PORTAIL%%] Research structures merged",
    'ref_structure_suppression'       =>  "[%%PORTAIL%%] Research structure deleted",

    'document_alert_ownership'              =>  '[%%PORTAIL%%][%%DOC_FORMAT%%] %%DOC_ID%% : Are you author of this document ?',
    'document_alert_refstruct'              => '[%%STRUCTNAME%%][%%DOC_FORMAT%%] New document online affiliated to the structure',
    'document_alert_admin'              => '[%%PORTAIL%%][%%DOC_FORMAT%%] New document online on the portal',
);