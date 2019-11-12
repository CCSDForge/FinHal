-- requetes SQL pour modification BdD pour blacklister certains utilisateur du transfert arXiv

CREATE TABLE NO_ARXIV (UID INT PRIMARY KEY NOT NULL, DATEBL DATE, COMMENT VARCHAR(255))