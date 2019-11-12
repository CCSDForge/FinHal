-- Add PATROL table to take pattrolling into account

CREATE TABLE `DOC_PATROL` (
  `IDENTIFIANT` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `SID` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `PSTATUS` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `UID` int(11) NOT NULL DEFAULT '0',
  `PDATE` datetime DEFAULT NULL,
  `PVERSION` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Patche mark as applied: must be elsewhere... but now...
INSERT INTO DB_PATCHES  (`FILE`) VALUES ('2019-09-01-PatrolTable.sql');
