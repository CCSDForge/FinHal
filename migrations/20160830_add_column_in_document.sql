-- 
-- Objectif: Mettre à jour la nouvelle colonne "TEXTAVAILABLE" de la table DOCUMENT de la base HALV3 
--

-- Cas où le document est un de type 'FILE' ou a un ARXIV ID ou un PUBMEDCENTRAL ID
UPDATE `DOCUMENT` SET TEXTAVAILABLE='1' WHERE FORMAT='file' OR (FORMAT='notice' AND EXISTS (SELECT * FROM `DOC_HASCOPY` WHERE DOCUMENT.DOCID=DOC_HASCOPY.DOCID AND (DOC_HASCOPY.code='arxiv' OR DOC_HASCOPY.code='pubmedcentral')))