-- requetes SQL pour modification BdD pour Ref Structure
-- table REF_STRUCTURE

ALTER TABLE REF_STRUCTURE ADD COLUMN LOCKED BOOLEAN NOT NULL AFTER VALID;