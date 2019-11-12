-- 
-- Objectif: Corriger certaines données des fichiers ProdInra et BMC afin de permettre une moderation par lot
-- 
-- Les fichiers associés aux dépots ProdInra et/ou BMC sont mal "typés"
--     Fichier principal mal determiné
--     "licence" inadaptée
--
-- ATTENTION: Commande en ligne a lancer apres le SQL
--
-- Utilisation: remplacer les hotes et port par les valeurs correctes
--         mysql -u root -p -h dbname4 HALV3 < CorectionProdInraBMC.sql
--
-- Auteur: BM
-- Specification: AM
--

-- Mettre les fichiers de ProdInra en publisherAgreement
UPDATE DOCUMENT JOIN DOC_FILE on DOCUMENT.DOCID=DOC_FILE.DOCID  set FILESOURCE='publisherAgreement' WHERE DOCUMENT.UID=132775 AND (DOCUMENT.DOCSTATUS =10 OR DOCUMENT.DOCSTATUS =0)

-- Les fichiers annexes (ne contenant pas article dans le nom), sont mis en MAIN=0
UPDATE DOC_FILE JOIN DOCUMENT  on DOCUMENT.DOCID=DOC_FILE.DOCID set DOC_FILE.MAIN=0 where DOCUMENT.UID=326461 AND DOCUMENT.DOCSTATUS =0 AND DOC_FILE.MAIN=1 AND FILENAME not like '%article%';

-- Les fichiers contenant "article" dans le nom, sont mis en MAIN=1
UPDATE DOC_FILE JOIN DOCUMENT on DOCUMENT.DOCID=DOC_FILE.DOCID set DOC_FILE.MAIN=1 where DOCUMENT.UID=326461 AND DOCUMENT.DOCSTATUS =0 AND DOC_FILE.MAIN=0 AND FILENAME like '%article%';

-- Les fichiers annexes (ne contenant pas article dans le nom), sont mis en FILETYPE=annex
UPDATE DOC_FILE JOIN DOCUMENT on DOCUMENT.DOCID=DOC_FILE.DOCID set DOC_FILE.FILETYPE='annex' where DOCUMENT.UID=326461 AND DOCUMENT.DOCSTATUS =0  AND FILENAME NOT LIKE '%article%' AND FILETYPE != 'annex';

--
SELECT "LANCER le script d effacement de cache"
SELECT "for i in `echo 'SELECT DOCID FROM DOCUMENT WHERE (DOCUMENT.UID=132775 OR DOCUMENT.UID=326461) AND (DOCUMENT.DOCSTATUS =10 OR DOCUMENT.DOCSTATUS =0)' | mysql  --skip-column-names -u root -p -h dbname4 HALV3` ; do dir=`printf '%08d' $i | sed 's+\(..\)+\1/+g'`; [ -d /cache/hal/production/docs/${dir} ] && rm -rf /cache/hal/production/docs/${dir} ;done"

