<?php

return [
    'mail_available_subject' =>  '[HALMS] FTP [New article %%HALMS_ID%% available]',
    'mail_available_content' =>  "Hello %%USER%%,\n\nA new article is available on FTP : %%HALMS_ARCHIVE%%.\n\nSincerely,\nHALMS-Team.",

    'mail_dcl_return_subject'=>  '[HALMS] New article %%HALMS_ID%% available',
    'mail_dcl_return_content'=>  "Hello %%USER%%,\n\nA new article has been convert by DCL : %%HALMS_ID%%.\n\nSincerely.",

    'mail_author_subject'=>  '[HALMS] Article %%HALMS_ID%% / PubMed Central',
    'mail_author_content'=>  "Dear %%USER%%,\n\nYour article %%DOC_ID%% '%%DOC_TITLE%%' is ready for transfer from HAL to PubMed Central.\nYou are invited to validate the result of the transformation before %%HALMS_LIMIT%%. For this you have to connect to http://halms.ccsd.cnrs.fr/ with your HAL login. Verifications have already been done, however errors due to the conversion may subsist and you are invited to point them into the dedicated field. Without answer from you at the date of %%HALMS_LIMIT%%, the article will be sent to PubMed Central.\n\nSincerely,\nHALMS-Team.",

    'mail_author_reporting_subject'=>  '[HALMS] Article %%HALMS_ID%% - Error reporting',
    'mail_author_reporting_content'=>  "Hello %%USER%%,\n\nThe author has reported somes errors:\n%%COMMENT%%.\n\nSincerely.",

    'mail_author_online_subject'   =>  "[HALMS] FTP [New article %%HALMS_ID%% online]",
    'mail_author_online_content'   =>   "Hello HALMS-Tech,\n\nA new article is online on PubMedCentral : %%HALMS_ID%% (http://www.pubmedcentral.nih.gov/articlerender.fcgi?tool=pmcentrez&artid=%%PMCID%%).\n\nSincerely,\nHALMS-Team."
];