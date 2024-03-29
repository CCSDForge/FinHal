-- requetes SQL pour modification BdD pour gestion partage de propriété 
-- table DOC_OWNER
ALTER TABLE DOC_OWNER DROP COLUMN ROLE;

ALTER TABLE DOC_OWNER ADD COLUMN IDENTIFIANT VARCHAR(50) NOT NULL AFTER UID;

UPDATE DOC_OWNER a, DOCUMENT b
    SET a.IDENTIFIANT = b.IDENTIFIANT
    WHERE a.DOCID = b.DOCID;

ALTER TABLE DOC_OWNER DROP COLUMN DOCID;


-- table DOC_OWNER_CLAIM

ALTER TABLE DOC_OWNER_CLAIM ADD COLUMN IDENTIFIANT VARCHAR(50) NOT NULL AFTER UID;

ALTER TABLE DOC_OWNER_CLAIM ADD COLUMN DATECRE DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL AFTER IDENTIFIANT;

UPDATE DOC_OWNER_CLAIM a, DOCUMENT b
    SET a.IDENTIFIANT = b.IDENTIFIANT
    WHERE a.DOCID = b.DOCID;

ALTER TABLE DOC_OWNER_CLAIM DROP PRIMARY KEY;

ALTER TABLE DOC_OWNER_CLAIM ADD PRIMARY KEY (UID,IDENTIFIANT);

ALTER TABLE DOC_OWNER_CLAIM DROP COLUMN DOCID;

-- table DOC_STAT_VISITOR

RENAME TABLE DOC_STAT_VISITOR TO STAT_VISITOR;
