-- MySQL dump 10.13  Distrib 5.7.28, for Linux (x86_64)
--
-- Host: ccsddb04    Database: HALV3
-- ------------------------------------------------------
-- Server version	5.6.46-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `COLLECTION_DOC_HIDDEN`
--

DROP TABLE IF EXISTS `COLLECTION_DOC_HIDDEN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `COLLECTION_DOC_HIDDEN` (
  `DOCID` int(10) unsigned NOT NULL DEFAULT '0',
  `SID` int(10) unsigned NOT NULL,
  `UID` int(10) unsigned NOT NULL,
  `DATESTAMP` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`DOCID`,`SID`,`UID`) USING BTREE,
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_SID` (`SID`),
  KEY `IDX_UID` (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 COMMENT='Documents à masquer dans le tamponnage des collections';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `COLLECTION_SETTINGS`
--

DROP TABLE IF EXISTS `COLLECTION_SETTINGS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `COLLECTION_SETTINGS` (
  `SID` int(11) NOT NULL,
  `SETTING` varchar(255) NOT NULL,
  `VALUE` text NOT NULL,
  PRIMARY KEY (`SID`,`SETTING`),
  KEY `IDX_SETTING` (`SETTING`),
  KEY `IDX_VALUE` (`VALUE`(333)),
  KEY `IDX_SID` (`SID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CV_STAT_COUNTER`
--

DROP TABLE IF EXISTS `CV_STAT_COUNTER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CV_STAT_COUNTER` (
  `STATID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `IDHAL` int(10) unsigned NOT NULL,
  `UID` int(10) unsigned NOT NULL DEFAULT '0',
  `VID` int(10) unsigned NOT NULL,
  `DHIT` date NOT NULL,
  `COUNTER` int(10) unsigned NOT NULL,
  PRIMARY KEY (`STATID`),
  UNIQUE KEY `U_STAT` (`IDHAL`,`VID`,`DHIT`,`UID`),
  KEY `IDX_DOCID` (`IDHAL`),
  KEY `IDX_DATE` (`DHIT`),
  KEY `IDX_VISIT` (`VID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOCUMENT`
--

DROP TABLE IF EXISTS `DOCUMENT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOCUMENT` (
  `DOCID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `IDENTIFIANT` varchar(50) NOT NULL DEFAULT '',
  `VERSION` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `PWD` varchar(8) NOT NULL,
  `DOCSTATUS` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `TYPDOC` varchar(50) NOT NULL DEFAULT 'UNDEFINED',
  `FORMAT` enum('file','annex','notice') NOT NULL DEFAULT 'file',
  `EXPORTREPEC` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `EXPORTOAI` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `UID` int(10) unsigned NOT NULL DEFAULT '1',
  `SID` int(10) unsigned NOT NULL,
  `INPUTTYPE` enum('WEB','WS','XML','SWORD') NOT NULL DEFAULT 'WEB',
  `TEXTAVAILABLE` tinyint(1) NOT NULL DEFAULT '0',
  `DATESUBMIT` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `DATEMODER` datetime DEFAULT NULL,
  `DATEMODIF` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`DOCID`),
  KEY `IDX_IDENTIFIANT` (`IDENTIFIANT`),
  KEY `IDX_VERSION` (`VERSION`),
  KEY `IDX_DOCSTATUS` (`DOCSTATUS`),
  KEY `IDX_TYPDOC` (`TYPDOC`),
  KEY `IDX_HAVEFILE` (`FORMAT`),
  KEY `IDX_SID` (`SID`),
  KEY `IDX_UID` (`UID`),
  KEY `IDX_DATE` (`DATESUBMIT`)
) ENGINE=MyISAM AUTO_INCREMENT=2370379 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_ARCHIVE`
--

DROP TABLE IF EXISTS `DOC_ARCHIVE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_ARCHIVE` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCID` int(10) unsigned NOT NULL COMMENT 'ID INTERNE DU DEPOT / LOT ',
  `IDPAC` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ID CINES DE L''ARCHIVE ASSOCIEE',
  `DATE_ACTION` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `STATUT` int(2) unsigned NOT NULL,
  `CODE_ERREUR` text,
  `ACTION` varchar(255) NOT NULL DEFAULT '',
  `INSTANCE` varchar(255) DEFAULT NULL,
  `CURRENT` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Identifie l''état courant du lot',
  PRIMARY KEY (`ID`),
  KEY `DOCID` (`DOCID`),
  KEY `DATE_ACTION` (`DATE_ACTION`),
  KEY `IDPAC` (`IDPAC`),
  KEY `STATUT` (`STATUT`)
) ENGINE=MyISAM AUTO_INCREMENT=3002727 DEFAULT CHARSET=utf8 COMMENT='Information d''archivage au CINES';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_AUTHOR`
--

DROP TABLE IF EXISTS `DOC_AUTHOR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_AUTHOR` (
  `DOCAUTHID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCID` int(10) unsigned NOT NULL,
  `AUTHORID` int(10) unsigned NOT NULL,
  `QUALITY` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'aut',
  PRIMARY KEY (`DOCAUTHID`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_AUTHOR` (`AUTHORID`)
) ENGINE=MyISAM AUTO_INCREMENT=196176548 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_AUTHOR_IDEXT`
--

DROP TABLE IF EXISTS `DOC_AUTHOR_IDEXT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_AUTHOR_IDEXT` (
  `AUTHIDEXTID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `AUTHORID` int(10) unsigned NOT NULL,
  `DOCID` int(10) unsigned NOT NULL DEFAULT '0',
  `SERVERID` int(10) unsigned NOT NULL,
  `ID` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`AUTHIDEXTID`),
  UNIQUE KEY `U_ID` (`AUTHORID`,`DOCID`,`SERVERID`,`ID`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_AUTH` (`AUTHORID`)
) ENGINE=MyISAM AUTO_INCREMENT=233555 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_AUTSTRUCT`
--

DROP TABLE IF EXISTS `DOC_AUTSTRUCT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_AUTSTRUCT` (
  `AUTSTRUCTID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCAUTHID` int(10) unsigned NOT NULL,
  `STRUCTID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`AUTSTRUCTID`),
  KEY `IDX_AUTH` (`DOCAUTHID`),
  KEY `IDX_STRUCT` (`STRUCTID`)
) ENGINE=MyISAM AUTO_INCREMENT=25235234 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_COMMENT`
--

DROP TABLE IF EXISTS `DOC_COMMENT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_COMMENT` (
  `COMMENTID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCID` int(10) unsigned NOT NULL,
  `UID` int(10) unsigned DEFAULT NULL,
  `GUESTN` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `GUESTP` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `GUESTM` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MESG` text COLLATE utf8_unicode_ci NOT NULL,
  `PUBLIC` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `DATECRE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`COMMENTID`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_UID` (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_DELETED`
--

DROP TABLE IF EXISTS `DOC_DELETED`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_DELETED` (
  `DOCID` int(10) unsigned NOT NULL,
  `IDENTIFIANT` varchar(50) NOT NULL,
  `OAISET` varchar(4000) DEFAULT NULL,
  `DATEDELETED` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`DOCID`),
  KEY `DATEDELETED` (`DATEDELETED`),
  KEY `IDENTIFIANT` (`IDENTIFIANT`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_FILE`
--

DROP TABLE IF EXISTS `DOC_FILE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_FILE` (
  `FILEID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCID` int(10) unsigned NOT NULL DEFAULT '0',
  `FILENAME` varchar(500) NOT NULL,
  `INFO` text,
  `MAIN` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `EXTENSION` varchar(20) NOT NULL,
  `TYPEMIME` varchar(200) NOT NULL,
  `SIZE` bigint(20) unsigned NOT NULL DEFAULT '0',
  `MD5` char(32) NOT NULL,
  `FILETYPE` varchar(10) NOT NULL DEFAULT 'file',
  `FILESOURCE` varchar(20) NOT NULL DEFAULT 'author',
  `SOURCE` enum('author','compilation','converted','unzipped') NOT NULL DEFAULT 'author',
  `DATEVISIBLE` date NOT NULL,
  `TYPEANNEX` varchar(50) NOT NULL,
  `IMAGETTE` int(10) DEFAULT NULL,
  `SEND` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ARCHIVED` datetime DEFAULT NULL COMMENT 'Date d''archivage si ok',
  PRIMARY KEY (`FILEID`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_MAIN` (`MAIN`)
) ENGINE=MyISAM AUTO_INCREMENT=2270239 DEFAULT CHARSET=utf8 COMMENT='Fichiers des documents';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_FILE_REQUEST`
--

DROP TABLE IF EXISTS `DOC_FILE_REQUEST`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_FILE_REQUEST` (
  `UID` int(10) unsigned NOT NULL,
  `DOCID` int(10) unsigned NOT NULL,
  `DATECRE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UID`,`DOCID`),
  UNIQUE KEY `IDX_DOCID` (`DOCID`),
  KEY `UID` (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_HALMS`
--

DROP TABLE IF EXISTS `DOC_HALMS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_HALMS` (
  `DOCID` int(10) unsigned NOT NULL,
  `DOCSTATUS` int(11) DEFAULT NULL,
  `DATECRE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DATEMODIF` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`DOCID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_HALMS_HISTORY`
--

DROP TABLE IF EXISTS `DOC_HALMS_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_HALMS_HISTORY` (
  `ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique dans la table',
  `DOCID` int(8) unsigned NOT NULL COMMENT 'Identifiant unique du dépôt',
  `STATUS` tinyint(4) unsigned NOT NULL COMMENT 'Etat du dépôt dans la procédure d''envoi sur PMC',
  `COMMENT` text COMMENT 'Texte explicatif si necessaire concernant l''action en cours',
  `UID` int(8) unsigned NOT NULL COMMENT 'Identifiant du user déclenchant l''action',
  `DATE_ACTION` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID` (`ID`),
  KEY `DOCID` (`DOCID`,`STATUS`)
) ENGINE=MyISAM AUTO_INCREMENT=10264 DEFAULT CHARSET=utf8mb4 COMMENT='Informations sur les dépôts manipulées par l''application HALMS';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_HASCOPY`
--

DROP TABLE IF EXISTS `DOC_HASCOPY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_HASCOPY` (
  `DOCID` int(10) unsigned NOT NULL,
  `CODE` varchar(50) NOT NULL,
  `LOCALID` varchar(100) NOT NULL,
  `SOURCE` varchar(25) NOT NULL DEFAULT 'web',
  `UID` int(11) NOT NULL DEFAULT '0',
  `DATECRE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `U_IDEXT` (`DOCID`,`CODE`(25),`LOCALID`) USING BTREE,
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_CODE` (`CODE`(25)) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_IDARXIV`
--

DROP TABLE IF EXISTS `DOC_IDARXIV`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_IDARXIV` (
  `DOCID` int(10) unsigned NOT NULL,
  `ARXIVID` varchar(25) DEFAULT NULL,
  `PENDING` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`DOCID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Relation DOCID HAL et infos arXiv';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_LINKEXT`
--

DROP TABLE IF EXISTS `DOC_LINKEXT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_LINKEXT` (
  `LINKEXTID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `LINKID` varchar(100) NOT NULL,
  `URL` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`LINKEXTID`),
  UNIQUE KEY `LINKID` (`LINKID`) USING BTREE,
  UNIQUE KEY `URL` (`LINKEXTID`)
) ENGINE=MyISAM AUTO_INCREMENT=658614 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_LOG`
--

DROP TABLE IF EXISTS `DOC_LOG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_LOG` (
  `LOGID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCID` int(10) unsigned NOT NULL,
  `UID` int(10) unsigned NOT NULL,
  `LOGACTION` enum('create','annotate','discussion','askmodif','modif','validate','moderate','update','online','version','addfile','copy','jref','domain','tampon','addtampon','deltampon','hide','delete','related','notice','share','remod','request','editmoderation','moved','merged') NOT NULL DEFAULT 'create',
  `MESG` text,
  `DATELOG` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`LOGID`),
  KEY `IDX_UID` (`UID`),
  KEY `IDX_ACTION` (`LOGACTION`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `DATELOG` (`DATELOG`)
) ENGINE=MyISAM AUTO_INCREMENT=177968753 DEFAULT CHARSET=utf8 COMMENT='Liste les modifications apportées aux articles';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_METADATA`
--

DROP TABLE IF EXISTS `DOC_METADATA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_METADATA` (
  `METAID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCID` int(10) unsigned NOT NULL,
  `METANAME` varchar(45) NOT NULL,
  `METAVALUE` text NOT NULL,
  `METAGROUP` varchar(15) DEFAULT NULL,
  `SOURCE` varchar(25) NOT NULL DEFAULT 'web',
  `UID` int(11) NOT NULL DEFAULT '0',
  `SID` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`METAID`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_NAME` (`METANAME`),
  KEY `IDX_METAVALUE` (`METAVALUE`(333)),
  KEY `IDX_METAGROUP` (`METAGROUP`),
  KEY `IDX_SID` (`SID`),
  KEY `IDX_UID` (`UID`)
) ENGINE=MyISAM AUTO_INCREMENT=54677644 DEFAULT CHARSET=utf8 COMMENT='Métadonnées des dépôts';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_OWNER`
--

DROP TABLE IF EXISTS `DOC_OWNER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_OWNER` (
  `OWNERID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UID` int(10) unsigned NOT NULL,
  `IDENTIFIANT` varchar(50) NOT NULL,
  PRIMARY KEY (`OWNERID`),
  KEY `IDX_USER` (`UID`),
  KEY `IDX_ID` (`IDENTIFIANT`)
) ENGINE=MyISAM AUTO_INCREMENT=4619137 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_OWNER_CLAIM`
--

DROP TABLE IF EXISTS `DOC_OWNER_CLAIM`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_OWNER_CLAIM` (
  `UID` int(11) unsigned NOT NULL,
  `IDENTIFIANT` varchar(50) NOT NULL,
  `DATECRE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UID`,`IDENTIFIANT`),
  KEY `UID` (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_REFERENCES`
--

DROP TABLE IF EXISTS `DOC_REFERENCES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_REFERENCES` (
  `REFID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCID` int(10) unsigned NOT NULL,
  `REFXML_ORIGINAL` mediumtext CHARACTER SET utf8mb4,
  `REFHTML` mediumtext CHARACTER SET utf8mb4,
  `REFXML` mediumtext CHARACTER SET utf8mb4,
  `REFSTATUS` enum('NOT_UPDATED','UPDATED','UPDATING') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NOT_UPDATED',
  `REFVALIDETY` enum('VALIDETED','NOT_VERIFIED','DELETED','BAD_FORMAT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NOT_VERIFIED',
  `DOI` text CHARACTER SET utf8mb4,
  `URL` text CHARACTER SET utf8mb4,
  `SOURCE` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Grobid',
  `TARGETDOCID` int(10) unsigned DEFAULT NULL,
  `EXTRACT_DATE` datetime DEFAULT CURRENT_TIMESTAMP,
  `UPDATE_DATE` datetime DEFAULT NULL,
  `PID` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`REFID`),
  KEY `DOCID` (`DOCID`),
  KEY `REFSTATUS` (`REFSTATUS`),
  KEY `PID` (`PID`),
  KEY `REFVALIDETY` (`REFVALIDETY`),
  KEY `TARGETDOCID` (`TARGETDOCID`)
) ENGINE=InnoDB AUTO_INCREMENT=28601281 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_RELATED`
--

DROP TABLE IF EXISTS `DOC_RELATED`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_RELATED` (
  `DOCID` int(10) unsigned NOT NULL,
  `IDENTIFIANT` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `RELATION` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `INFO` text COLLATE utf8_unicode_ci,
  `DATEMODIF` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`DOCID`,`IDENTIFIANT`,`RELATION`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_ID` (`IDENTIFIANT`),
  KEY `IDX_RELATION` (`RELATION`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_RESEARCHDATA`
--

DROP TABLE IF EXISTS `DOC_RESEARCHDATA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_RESEARCHDATA` (
  `RESEARCHDATAID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DATAID` varchar(100) NOT NULL,
  `SOURCE` varchar(100) DEFAULT NULL,
  `TITLE` varchar(255) NOT NULL,
  `PUBLISHER` varchar(255) NOT NULL,
  `DATE` year(4) DEFAULT NULL,
  PRIMARY KEY (`RESEARCHDATAID`),
  UNIQUE KEY `DATAID` (`DATAID`),
  KEY `SOURCE` (`SOURCE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_SAMEAS`
--

DROP TABLE IF EXISTS `DOC_SAMEAS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_SAMEAS` (
  `DELETEDID` varchar(50) CHARACTER SET utf8 NOT NULL,
  `CURRENTID` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`DELETEDID`),
  KEY `IDX_CURRENT` (`CURRENTID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_STAT_COUNTER`
--

DROP TABLE IF EXISTS `DOC_STAT_COUNTER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_STAT_COUNTER` (
  `STATID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCID` int(10) unsigned NOT NULL,
  `UID` int(10) unsigned NOT NULL DEFAULT '0',
  `CONSULT` varchar(20) NOT NULL DEFAULT 'notice',
  `FILEID` int(10) unsigned NOT NULL DEFAULT '0',
  `VID` int(10) unsigned NOT NULL,
  `DHIT` date NOT NULL,
  `COUNTER` int(10) unsigned NOT NULL,
  PRIMARY KEY (`STATID`),
  UNIQUE KEY `U_STAT` (`DOCID`,`CONSULT`,`FILEID`,`VID`,`DHIT`,`UID`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_FILE` (`FILEID`),
  KEY `IDX_CONSULT` (`CONSULT`),
  KEY `IDX_DATE` (`DHIT`),
  KEY `IDX_VISIT` (`VID`)
) ENGINE=MyISAM AUTO_INCREMENT=584715450 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_SWH`
--

DROP TABLE IF EXISTS `DOC_SWH`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_SWH` (
  `DOCID` int(10) unsigned NOT NULL,
  `REMOTEID` varchar(50) DEFAULT NULL,
  `PENDING` varchar(255) DEFAULT NULL,
  `MODIFIED` date DEFAULT NULL,
  PRIMARY KEY (`DOCID`),
  KEY `RemoteIDidx` (`REMOTEID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOC_TAMPON`
--

DROP TABLE IF EXISTS `DOC_TAMPON`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOC_TAMPON` (
  `DOCID` int(10) unsigned NOT NULL DEFAULT '0',
  `SID` int(10) unsigned NOT NULL,
  `UID` int(10) unsigned NOT NULL,
  `DATESTAMP` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`DOCID`,`SID`),
  KEY `IDX_DOCID` (`DOCID`),
  KEY `IDX_SID` (`SID`),
  KEY `IDX_UID` (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='tampon sur les dépôts';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GROBID_REFERENCES`
--

DROP TABLE IF EXISTS `GROBID_REFERENCES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GROBID_REFERENCES` (
  `DOCID` int(10) unsigned NOT NULL,
  `GRODIB_PROCESS` enum('Executing','Executed','Not_Executed') NOT NULL DEFAULT 'Not_Executed',
  `PID` int(11) unsigned DEFAULT NULL,
  `GROBID_DATE` datetime DEFAULT NULL,
  `SAVE_DATE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`DOCID`),
  KEY `PID` (`PID`),
  KEY `GRODIB_PROCESS` (`GRODIB_PROCESS`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Used to store docs, need to be processed by GROBID';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `MAIL_TEMPLATE`
--

DROP TABLE IF EXISTS `MAIL_TEMPLATE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MAIL_TEMPLATE` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PARENTID` int(11) DEFAULT NULL,
  `SID` int(11) DEFAULT NULL,
  `KEY` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NEWS`
--

DROP TABLE IF EXISTS `NEWS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `NEWS` (
  `NEWSID` int(11) NOT NULL AUTO_INCREMENT,
  `SID` int(11) NOT NULL,
  `UID` int(11) NOT NULL,
  `LINK` varchar(2000) CHARACTER SET latin1 NOT NULL,
  `ONLINE` tinyint(4) NOT NULL,
  `DATE_POST` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`NEWSID`)
) ENGINE=MyISAM AUTO_INCREMENT=1398 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NO_ARXIV`
--

DROP TABLE IF EXISTS `NO_ARXIV`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `NO_ARXIV` (
  `UID` int(11) NOT NULL,
  `DATEBL` date DEFAULT NULL,
  `COMMENT` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `OWNER_TOKENS`
--

DROP TABLE IF EXISTS `OWNER_TOKENS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `OWNER_TOKENS` (
  `TID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UID` int(10) unsigned NOT NULL,
  `DOCID` int(10) unsigned NOT NULL,
  `TOKEN` varchar(40) NOT NULL,
  `TIME_MODIFIED` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `USAGE` enum('UNSHARE') NOT NULL,
  PRIMARY KEY (`TID`)
) ENGINE=InnoDB AUTO_INCREMENT=269374 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PORTAIL_DOMAIN`
--

DROP TABLE IF EXISTS `PORTAIL_DOMAIN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PORTAIL_DOMAIN` (
  `SID` int(10) unsigned NOT NULL,
  `ID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`SID`,`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PORTAIL_SETTINGS`
--

DROP TABLE IF EXISTS `PORTAIL_SETTINGS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PORTAIL_SETTINGS` (
  `SID` int(11) unsigned NOT NULL,
  `SETTING` varchar(255) NOT NULL,
  `VALUE` text NOT NULL,
  PRIMARY KEY (`SID`,`SETTING`),
  KEY `PSV` (`VALUE`(300))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_ALIAS`
--

DROP TABLE IF EXISTS `REF_ALIAS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_ALIAS` (
  `REFID` int(10) unsigned NOT NULL COMMENT 'nouvel id valide',
  `REFNOM` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'nom du référentiel',
  `OLDREFID` int(10) unsigned NOT NULL COMMENT 'ancien id fusionné',
  `OLDREFMD5` binary(16) DEFAULT NULL,
  `DATEMODIF` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`REFID`,`REFNOM`,`OLDREFID`),
  KEY `DATEMODIF` (`DATEMODIF`),
  KEY `OLDREFID` (`OLDREFID`),
  KEY `REFNOM` (`REFNOM`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_AUTHOR`
--

DROP TABLE IF EXISTS `REF_AUTHOR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_AUTHOR` (
  `AUTHORID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `IDHAL` int(10) unsigned NOT NULL DEFAULT '0',
  `FIRSTNAME` varchar(1000) NOT NULL,
  `LASTNAME` varchar(1000) NOT NULL,
  `MIDDLENAME` varchar(500) DEFAULT NULL,
  `EMAIL` varchar(300) DEFAULT NULL,
  `URL` varchar(500) DEFAULT NULL,
  `STRUCTID` int(10) unsigned NOT NULL DEFAULT '0',
  `MD5` binary(16) DEFAULT NULL,
  `VALID` enum('VALID','OLD','INCOMING') NOT NULL DEFAULT 'INCOMING',
  PRIMARY KEY (`AUTHORID`),
  UNIQUE KEY `U_MD5` (`MD5`),
  KEY `IDX_IDHAL` (`IDHAL`),
  KEY `IDX_STRUCTID` (`STRUCTID`),
  KEY `IDX_EMAIL` (`EMAIL`),
  KEY `IDX_FIRST` (`FIRSTNAME`(333)),
  KEY `IDX_LAST` (`LASTNAME`(333))
) ENGINE=MyISAM AUTO_INCREMENT=11658477 DEFAULT CHARSET=utf8 COMMENT='Author for papers';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%.in2p3.fr`*/ /*!50003 TRIGGER `TRIG_INS_AUTHOR_MD5` BEFORE INSERT ON `REF_AUTHOR` FOR EACH ROW BEGIN

IF ( NEW.IDHAL IS NULL OR NEW.IDHAL = '' ) THEN
 SET NEW.IDHAL = 0;
END IF;
IF ( NEW.LASTNAME IS NULL OR NEW.LASTNAME = '' ) THEN
 SET NEW.LASTNAME = '';
END IF;
IF ( NEW.FIRSTNAME IS NULL OR NEW.FIRSTNAME = '' ) THEN
 SET NEW.FIRSTNAME = '';
END IF;
IF ( NEW.MIDDLENAME IS NULL OR NEW.MIDDLENAME = '' ) THEN
 SET NEW.MIDDLENAME = NULL;
END IF;
IF ( NEW.EMAIL IS NULL OR NEW.EMAIL = '' ) THEN
 SET NEW.EMAIL = NULL;
END IF;
IF ( NEW.URL IS NULL OR NEW.URL = '' ) THEN
 SET NEW.URL = NULL;
END IF;
IF ( NEW.STRUCTID IS NULL OR NEW.STRUCTID = '' ) THEN
 SET NEW.STRUCTID = 0;
END IF;
set NEW.MD5 = UNHEX(MD5(CONCAT_WS('',NEW.IDHAL,'idhal',LOWER(NEW.LASTNAME),'lastname',LOWER(NEW.FIRSTNAME),'firstname',LOWER(NEW.MIDDLENAME),'middlename',LOWER(NEW.EMAIL),'email',LOWER(NEW.URL),'url',NEW.STRUCTID,'structid')));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%.in2p3.fr`*/ /*!50003 TRIGGER `TRIG_UPT_AUTHOR_MD5` BEFORE UPDATE ON `REF_AUTHOR` FOR EACH ROW BEGIN
IF ( NEW.IDHAL IS NULL OR NEW.IDHAL = '' ) THEN
 SET NEW.IDHAL = 0;
END IF;
IF ( NEW.LASTNAME IS NULL OR NEW.LASTNAME = '' ) THEN
 SET NEW.LASTNAME = '';
END IF;
IF ( NEW.FIRSTNAME IS NULL OR NEW.FIRSTNAME = '' ) THEN
 SET NEW.FIRSTNAME = '';
END IF;
IF ( NEW.MIDDLENAME IS NULL OR NEW.MIDDLENAME = '' ) THEN
 SET NEW.MIDDLENAME = NULL;
END IF;
IF ( NEW.EMAIL IS NULL OR NEW.EMAIL = '' ) THEN
 SET NEW.EMAIL = NULL;
END IF;
IF ( NEW.URL IS NULL OR NEW.URL = '' ) THEN
 SET NEW.URL = NULL;
END IF;
IF ( NEW.STRUCTID IS NULL OR NEW.STRUCTID = '' ) THEN
 SET NEW.STRUCTID = 0;
END IF;
set NEW.MD5 = UNHEX(MD5(CONCAT_WS('',NEW.IDHAL,'idhal',LOWER(NEW.LASTNAME),'lastname',LOWER(NEW.FIRSTNAME),'firstname',LOWER(NEW.MIDDLENAME),'middlename',LOWER(NEW.EMAIL),'email',LOWER(NEW.URL),'url',NEW.STRUCTID,'structid')));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `REF_DOMAIN`
--

DROP TABLE IF EXISTS `REF_DOMAIN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_DOMAIN` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CODE` varchar(50) NOT NULL,
  `PARENT` int(10) unsigned NOT NULL DEFAULT '0',
  `LEVEL` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `HAVENEXT` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `U_CODE` (`CODE`,`PARENT`)
) ENGINE=MyISAM AUTO_INCREMENT=723 DEFAULT CHARSET=utf8 COMMENT='Domaines de publication';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_DOMAIN_ARXIV`
--

DROP TABLE IF EXISTS `REF_DOMAIN_ARXIV`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_DOMAIN_ARXIV` (
  `CODE` varchar(50) NOT NULL,
  `ARXIV` varchar(50) NOT NULL,
  `LIBELLE` varchar(255) NOT NULL,
  PRIMARY KEY (`CODE`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_HCERES`
--

DROP TABLE IF EXISTS `REF_HCERES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_HCERES` (
  `HCERESID` int(11) NOT NULL,
  `CODE_UAI` varchar(20) DEFAULT NULL,
  `CODE_RNSR` varchar(12) DEFAULT NULL,
  `CODE_ACCREDITATION` varchar(12) DEFAULT NULL,
  `NOM` varchar(2000) NOT NULL,
  `NOM_USAGE` varchar(2000) DEFAULT NULL,
  `NOM_ALIAS` varchar(2000) DEFAULT NULL,
  `SIGLE` varchar(50) DEFAULT NULL,
  `TYPEHCERES` enum('CT','EF','ER','FF','FE','CE','EE') NOT NULL,
  `STYPEHCERES` enum('CT','UN','EI','OR','AU','UR','SF','CC','CH','UM','AE','LI','LP','GL','MA','GM','ED','CF','PA','CE','EE','FE','CR','CHP') NOT NULL,
  `ADRESSE` varchar(500) DEFAULT NULL,
  `PAYSID` char(2) NOT NULL DEFAULT 'fr',
  `VILLE` varchar(50) DEFAULT NULL,
  `REGION` varchar(200) DEFAULT NULL,
  `NEW_HCERESID` int(11) DEFAULT NULL,
  `VALID` enum('VALID','OLD','INCOMING') NOT NULL DEFAULT 'VALID',
  `MD5` binary(16) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Référentiel HCERES';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_HCERES_OLD`
--

DROP TABLE IF EXISTS `REF_HCERES_OLD`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_HCERES_OLD` (
  `HCERESID` int(11) NOT NULL,
  `CODE_UAI` varchar(20) DEFAULT NULL,
  `CODE_RNSR` varchar(12) DEFAULT NULL,
  `CODE_ACCREDITATION` varchar(12) DEFAULT NULL,
  `NOM` varchar(2000) NOT NULL,
  `NOM_USAGE` varchar(2000) DEFAULT NULL,
  `NOM_ALIAS` varchar(2000) DEFAULT NULL,
  `SIGLE` varchar(50) DEFAULT NULL,
  `TYPEHCERES` enum('CT','EF','ER','FF','FE','CE','EE') NOT NULL,
  `STYPEHCERES` enum('CT','UN','EI','OR','AU','UR','SF','CC','CH','UM','AE','LI','LP','GL','MA','GM','ED','CF','PA','CE','EE','FE','CR','CHP') NOT NULL,
  `ADRESSE` varchar(500) DEFAULT NULL,
  `PAYSID` char(2) NOT NULL DEFAULT 'fr',
  `VILLE` varchar(50) DEFAULT NULL,
  `REGION` varchar(200) DEFAULT NULL,
  `NEW_HCERESID` int(11) DEFAULT NULL,
  `VALID` enum('VALID','OLD','INCOMING') NOT NULL DEFAULT 'VALID',
  `MD5` binary(16) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Référentiel HCERES';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_IDHAL`
--

DROP TABLE IF EXISTS `REF_IDHAL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_IDHAL` (
  `IDHAL` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UID` int(10) unsigned NOT NULL,
  `URI` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DATEMODIF` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`IDHAL`),
  UNIQUE KEY `IDX_UID` (`UID`),
  UNIQUE KEY `U_URI` (`URI`)
) ENGINE=MyISAM AUTO_INCREMENT=179508 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_IDHAL_CV`
--

DROP TABLE IF EXISTS `REF_IDHAL_CV`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_IDHAL_CV` (
  `CVID` int(11) NOT NULL AUTO_INCREMENT,
  `IDHAL` int(11) NOT NULL,
  `TITLE` varchar(5000) NOT NULL,
  `CONTENT` mediumtext NOT NULL,
  `TYPDOC` varchar(5000) NOT NULL,
  `WIDGET` varchar(5000) NOT NULL,
  `WIDGET_EXT` varchar(5000) NOT NULL,
  `CSS` text NOT NULL,
  `THEME` varchar(200) NOT NULL,
  PRIMARY KEY (`CVID`),
  KEY `IDHAL` (`IDHAL`)
) ENGINE=MyISAM AUTO_INCREMENT=61508 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_IDHAL_IDEXT`
--

DROP TABLE IF EXISTS `REF_IDHAL_IDEXT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_IDHAL_IDEXT` (
  `IDHAL` int(10) unsigned NOT NULL,
  `SERVERID` int(10) unsigned NOT NULL,
  `ID` varchar(310) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`IDHAL`,`SERVERID`,`ID`),
  KEY `IDX_IDHAL` (`IDHAL`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_JOURNAL`
--

DROP TABLE IF EXISTS `REF_JOURNAL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_JOURNAL` (
  `JID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `JNAME` varchar(500) NOT NULL,
  `SHORTNAME` varchar(255) DEFAULT NULL,
  `ISSN` varchar(255) DEFAULT NULL,
  `EISSN` varchar(255) DEFAULT NULL,
  `PUBLISHER` varchar(255) DEFAULT NULL,
  `ROOTDOI` varchar(255) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `DATEMODIF` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `VALID` enum('VALID','OLD','INCOMING') NOT NULL DEFAULT 'INCOMING',
  `MD5` binary(16) DEFAULT NULL,
  `SHERPA_COLOR` varchar(25) DEFAULT NULL,
  `SHERPA_PREPRINT` enum('','can','cannot','restricted','unclear','unknown') DEFAULT NULL,
  `SHERPA_POSTPRINT` enum('','can','cannot','restricted','unclear','unknown') DEFAULT NULL,
  `SHERPA_PRE_REST` text,
  `SHERPA_POST_REST` text,
  `SHERPA_COND` text,
  `SHERPA_DATE` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`JID`),
  UNIQUE KEY `U_MD5` (`MD5`),
  KEY `IDX_NAME` (`JNAME`(333)),
  KEY `IDX_VALID` (`VALID`)
) ENGINE=MyISAM AUTO_INCREMENT=146658 DEFAULT CHARSET=utf8 COMMENT='Référentiel des revues';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%.in2p3.fr`*/ /*!50003 TRIGGER `TRIG_INS_JOURNAL_MD5` BEFORE INSERT ON `REF_JOURNAL` FOR EACH ROW set NEW.MD5 = UNHEX(MD5(CONCAT_WS('','jname',LOWER(NEW.JNAME),'issn',LOWER(IFNULL(NEW.ISSN,'')),'eissn',LOWER(IFNULL(NEW.EISSN,'')),'publisher',LOWER(IFNULL(NEW.PUBLISHER,''))))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%.in2p3.fr`*/ /*!50003 TRIGGER `TRIG_UPT_JOURNAL_MD5` BEFORE UPDATE ON `REF_JOURNAL` FOR EACH ROW set NEW.MD5 = UNHEX(MD5(CONCAT_WS('','jname',LOWER(NEW.JNAME),'issn',LOWER(IFNULL(NEW.ISSN,'')),'eissn',LOWER(IFNULL(NEW.EISSN,'')),'publisher',LOWER(IFNULL(NEW.PUBLISHER,''))))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `REF_LOG`
--

DROP TABLE IF EXISTS `REF_LOG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_LOG` (
  `ID` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `ID_TAB` int(10) unsigned NOT NULL COMMENT 'identifiant unique de l''enregistrement modifie dans sa table d''origine',
  `TABLE_NAME` varchar(255) NOT NULL COMMENT 'Table de l''enregistrement',
  `DATE_ACTION` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `UID` int(10) unsigned NOT NULL COMMENT 'identifiant de l''utilisateur',
  `ACTION` enum('MODIFIED','CREATED','DELETED','REPLACED_BY','REPLACE','MIGRATION','BOUNDED','UNBOUNDED') NOT NULL DEFAULT 'MODIFIED' COMMENT 'Type de modification',
  `PREV_VALUES` text COMMENT 'Sauvegarde valeurs precedentes',
  PRIMARY KEY (`ID`),
  KEY `UID` (`UID`)
) ENGINE=MyISAM AUTO_INCREMENT=2158236 DEFAULT CHARSET=utf8 COMMENT='Trace des modifications sur un element du referentiel';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_METADATA`
--

DROP TABLE IF EXISTS `REF_METADATA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_METADATA` (
  `METAID` int(11) NOT NULL AUTO_INCREMENT,
  `SID` int(11) NOT NULL,
  `METANAME` varchar(1000) NOT NULL,
  `METAVALUE` varchar(1000) NOT NULL,
  `SORT` int(11) NOT NULL,
  PRIMARY KEY (`METAID`),
  UNIQUE KEY `U_META` (`METANAME`(50),`METAVALUE`(200),`SID`),
  KEY `IDX_SID` (`SID`),
  KEY `IDX_METANAME` (`METANAME`(333))
) ENGINE=MyISAM AUTO_INCREMENT=3667 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_PROJANR`
--

DROP TABLE IF EXISTS `REF_PROJANR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_PROJANR` (
  `ANRID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TITRE` varchar(500) DEFAULT NULL,
  `ACRONYME` varchar(500) DEFAULT NULL,
  `REFERENCE` varchar(500) DEFAULT NULL,
  `INTITULE` varchar(500) DEFAULT NULL,
  `ACROAPPEL` varchar(500) DEFAULT NULL,
  `ANNEE` year(4) DEFAULT NULL,
  `VALID` enum('VALID','OLD','INCOMING') NOT NULL DEFAULT 'INCOMING',
  `MD5` binary(16) DEFAULT NULL,
  PRIMARY KEY (`ANRID`),
  UNIQUE KEY `U_MD5` (`MD5`),
  KEY `IDX_TITRE` (`TITRE`(333)),
  KEY `IDX_REF` (`REFERENCE`(333)),
  KEY `IDX_ACRONYME` (`ACRONYME`(50))
) ENGINE=MyISAM AUTO_INCREMENT=50324 DEFAULT CHARSET=utf8 COMMENT='liste de projets ANR';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%.in2p3.fr`*/ /*!50003 TRIGGER `TRIG_INS_ANR_MD5` BEFORE INSERT ON `REF_PROJANR` FOR EACH ROW set NEW.MD5 = UNHEX(MD5(CONCAT_WS('','titre',LOWER(NEW.TITRE),'acronyme',LOWER(NEW.ACRONYME),'reference',LOWER(NEW.REFERENCE)))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%.in2p3.fr`*/ /*!50003 TRIGGER `TRIG_UPT_ANR_MD5` BEFORE UPDATE ON `REF_PROJANR` FOR EACH ROW set NEW.MD5 = UNHEX(MD5(CONCAT_WS('','titre',LOWER(NEW.TITRE),'acronyme',LOWER(NEW.ACRONYME),'reference',LOWER(NEW.REFERENCE)))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `REF_PROJEUROP`
--

DROP TABLE IF EXISTS `REF_PROJEUROP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_PROJEUROP` (
  `PROJEUROPID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NUMERO` varchar(20) DEFAULT NULL,
  `ACRONYME` varchar(100) NOT NULL,
  `TITRE` varchar(500) DEFAULT NULL,
  `FUNDEDBY` varchar(200) DEFAULT NULL,
  `SDATE` date DEFAULT NULL,
  `EDATE` date DEFAULT NULL,
  `CALLID` varchar(200) DEFAULT NULL,
  `VALID` enum('VALID','OLD','INCOMING') NOT NULL DEFAULT 'INCOMING',
  `MD5` binary(16) DEFAULT NULL,
  PRIMARY KEY (`PROJEUROPID`),
  UNIQUE KEY `U_MD5` (`MD5`),
  KEY `IDX_TITRE` (`TITRE`(200)),
  KEY `IDX_ACRONYME` (`ACRONYME`),
  KEY `IDX_NUMBER` (`NUMERO`)
) ENGINE=MyISAM AUTO_INCREMENT=712765 DEFAULT CHARSET=utf8 COMMENT='Référentiel des Projets Eropéens';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%.in2p3.fr`*/ /*!50003 TRIGGER `TRIG_INS_PEUROP_MD5` BEFORE INSERT ON `REF_PROJEUROP` FOR EACH ROW set NEW.MD5 = UNHEX(MD5(CONCAT_WS('','numero',LOWER(IFNULL(NEW.NUMERO,'')),'acronyme',LOWER(NEW.ACRONYME),'titre',LOWER(IFNULL(NEW.TITRE,''))))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`%.in2p3.fr`*/ /*!50003 TRIGGER `TRIG_UPT_PEUROP_MD5` BEFORE UPDATE ON `REF_PROJEUROP` FOR EACH ROW set NEW.MD5 = UNHEX(MD5(CONCAT_WS('','numero',LOWER(IFNULL(NEW.NUMERO,'')),'acronyme',LOWER(NEW.ACRONYME),'titre',LOWER(IFNULL(NEW.TITRE,''))))) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `REF_SERVEREXT`
--

DROP TABLE IF EXISTS `REF_SERVEREXT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_SERVEREXT` (
  `SERVERID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NAME` varchar(100) CHARACTER SET utf8 NOT NULL,
  `URL` varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  `TYPE` enum('A','S','AS','U') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A',
  `ORDER` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`SERVERID`),
  KEY `IDX_TYPE` (`TYPE`),
  KEY `TYPE` (`TYPE`),
  KEY `ORDER` (`ORDER`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_STRUCTURE`
--

DROP TABLE IF EXISTS `REF_STRUCTURE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_STRUCTURE` (
  `STRUCTID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `SIGLE` varchar(500) DEFAULT NULL,
  `STRUCTNAME` varchar(500) NOT NULL,
  `ADDRESS` text,
  `PAYSID` char(2) NOT NULL DEFAULT 'fr',
  `URL` varchar(500) DEFAULT NULL,
  `SDATE` date DEFAULT NULL,
  `EDATE` date DEFAULT NULL,
  `TYPESTRUCT` enum('researchteam','department','laboratory','institution','regrouplaboratory','regroupinstitution') NOT NULL DEFAULT 'laboratory',
  `MD5` binary(16) DEFAULT NULL,
  `DATEMODIF` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `VALID` enum('VALID','OLD','INCOMING') NOT NULL DEFAULT 'INCOMING' COMMENT 'Etat de la structure',
  `LOCKED` tinyint(1) NOT NULL,
  PRIMARY KEY (`STRUCTID`) USING BTREE,
  UNIQUE KEY `U_MD5` (`MD5`),
  KEY `IDX_SIGLE` (`SIGLE`(100)),
  KEY `IDX_NAME` (`STRUCTNAME`(333)),
  KEY `IDX_TYPE` (`TYPESTRUCT`),
  KEY `IDX_VALID` (`VALID`)
) ENGINE=MyISAM AUTO_INCREMENT=574194 DEFAULT CHARSET=utf8 COMMENT='Structures de recherche';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_STRUCTURE_IDEXT`
--

DROP TABLE IF EXISTS `REF_STRUCTURE_IDEXT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_STRUCTURE_IDEXT` (
  `STRUCTID` int(10) unsigned NOT NULL,
  `SERVERID` int(10) unsigned NOT NULL,
  `ID` varchar(200) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`STRUCTID`,`SERVERID`,`ID`),
  KEY `IDX_IDHAL` (`STRUCTID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_STRUCT_LINK`
--

DROP TABLE IF EXISTS `REF_STRUCT_LINK`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_STRUCT_LINK` (
  `OLDSTRUCTID` int(10) unsigned NOT NULL,
  `CURSTRUCTID` int(10) unsigned NOT NULL,
  `RELATION` enum('closed','merged','splited') NOT NULL DEFAULT 'closed',
  `DATELINK` datetime NOT NULL,
  PRIMARY KEY (`OLDSTRUCTID`,`CURSTRUCTID`),
  KEY `IDX_NEW` (`CURSTRUCTID`),
  KEY `IDX_OLD` (`OLDSTRUCTID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_STRUCT_PARENT`
--

DROP TABLE IF EXISTS `REF_STRUCT_PARENT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_STRUCT_PARENT` (
  `STRUCTID` int(10) unsigned NOT NULL,
  `PARENTID` int(10) unsigned NOT NULL,
  `CODE` varchar(300) NOT NULL DEFAULT '',
  UNIQUE KEY `U_TUTELLE` (`STRUCTID`,`PARENTID`,`CODE`),
  KEY `IDX_STRUCT` (`STRUCTID`),
  KEY `IDX_PARENT` (`PARENTID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REF_UPDATE_DOC`
--

DROP TABLE IF EXISTS `REF_UPDATE_DOC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REF_UPDATE_DOC` (
  `UPDATEID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `REF` enum('ref_journal','ref_projanr','ref_projeurop','ref_author','ref_structure') NOT NULL,
  `DELETEDID` text,
  `CURRENTID` int(10) unsigned NOT NULL,
  `STATUS` enum('todo','locked','error') NOT NULL DEFAULT 'todo',
  `DATEMODIF` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`UPDATEID`),
  KEY `STATUS` (`STATUS`)
) ENGINE=MyISAM AUTO_INCREMENT=442210 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SITE`
--

DROP TABLE IF EXISTS `SITE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SITE` (
  `SID` int(11) NOT NULL AUTO_INCREMENT,
  `TYPE` enum('PORTAIL','COLLECTION') NOT NULL DEFAULT 'COLLECTION',
  `SITE` varchar(255) NOT NULL,
  `ID` varchar(10) DEFAULT NULL,
  `URL` varchar(200) DEFAULT NULL,
  `NAME` varchar(255) NOT NULL,
  `CATEGORY` enum('GEN','INSTITUTION','THEME','PRES','UNIV','ECOLE','LABO','COLLOQUE','REVUE','AUTRE','SET','COMUE') NOT NULL DEFAULT 'INSTITUTION',
  `DATE_CREATION` date NOT NULL,
  `CONTACT` varchar(255) DEFAULT NULL,
  `IMAGETTE` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`SID`),
  UNIQUE KEY `U_SITE` (`SITE`,`TYPE`),
  KEY `IDX_TYPE` (`TYPE`),
  KEY `IDX_SITE` (`SITE`),
  KEY `IDX_NAME` (`NAME`)
) ENGINE=MyISAM AUTO_INCREMENT=7650 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SITE_ALIAS`
--

DROP TABLE IF EXISTS `SITE_ALIAS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SITE_ALIAS` (
  `ALIASID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `SID` int(11) unsigned NOT NULL,
  `URL` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`ALIASID`),
  KEY `SID` (`SID`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SITE_PARENT`
--

DROP TABLE IF EXISTS `SITE_PARENT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SITE_PARENT` (
  `SID` int(10) unsigned NOT NULL,
  `SPARENT` int(10) unsigned NOT NULL,
  PRIMARY KEY (`SID`,`SPARENT`),
  KEY `IDX_SID` (`SID`),
  KEY `IDX_PARENT` (`SPARENT`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 COMMENT='Table des articles tamponnés sur PAOL';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `STAT_VISITOR`
--

DROP TABLE IF EXISTS `STAT_VISITOR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `STAT_VISITOR` (
  `VID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `IP` int(10) unsigned NOT NULL,
  `AGENT` varchar(2000) NOT NULL DEFAULT '',
  `ROBOT` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `DOMAIN` varchar(100) DEFAULT NULL,
  `CONTINENT` varchar(50) DEFAULT NULL,
  `COUNTRY` varchar(50) DEFAULT NULL,
  `CITY` varchar(50) DEFAULT NULL,
  `LAT` float DEFAULT NULL,
  `LON` float DEFAULT NULL,
  PRIMARY KEY (`VID`),
  UNIQUE KEY `U_VISITOR` (`IP`,`AGENT`(300)),
  KEY `IDX_IP` (`IP`),
  KEY `IDX_ROBO` (`ROBOT`),
  KEY `IDX_CONTINENT` (`CONTINENT`),
  KEY `IDX_COUNTRY` (`COUNTRY`),
  KEY `IDX_CITY` (`CITY`)
) ENGINE=MyISAM AUTO_INCREMENT=121246539 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER`
--

DROP TABLE IF EXISTS `USER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER` (
  `UID` int(11) NOT NULL,
  `SCREEN_NAME` varchar(100) NOT NULL,
  `LANGUEID` varchar(2) NOT NULL DEFAULT 'fr',
  `NBDOCREF` int(11) NOT NULL DEFAULT '0',
  `NBDOCSCI` int(11) NOT NULL DEFAULT '0',
  `NBDOCVIS` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`UID`),
  KEY `IDX_LANG` (`LANGUEID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_CONNEXION`
--

DROP TABLE IF EXISTS `USER_CONNEXION`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_CONNEXION` (
  `UID` int(11) NOT NULL,
  `SID` int(11) NOT NULL,
  `NB_CONNEXION` int(11) NOT NULL DEFAULT '0',
  `FIRST_CONNEXION` datetime NOT NULL,
  `LAST_CONNEXION` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`UID`,`SID`),
  KEY `UID` (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_ID_ASSOCIATION`
--

DROP TABLE IF EXISTS `USER_ID_ASSOCIATION`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_ID_ASSOCIATION` (
  `uid` varchar(100) NOT NULL,
  `federation` varchar(250) NOT NULL,
  `id_federation` varchar(250) NOT NULL,
  `uidCcsd` varchar(15) NOT NULL,
  `nom` varchar(150) DEFAULT NULL,
  `prenom` varchar(150) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `valid` tinyint(1) NOT NULL,
  PRIMARY KEY (`uid`,`federation`,`id_federation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_LIBRARY_DOC`
--

DROP TABLE IF EXISTS `USER_LIBRARY_DOC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_LIBRARY_DOC` (
  `LIBDOCID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UID` int(10) unsigned NOT NULL,
  `LIBSHELFID` int(10) unsigned NOT NULL,
  `IDENTIFIANT` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`LIBDOCID`),
  UNIQUE KEY `U_DOCSHELF` (`LIBSHELFID`,`IDENTIFIANT`),
  KEY `IDX_UID` (`UID`),
  KEY `IDX_LIB` (`LIBSHELFID`)
) ENGINE=MyISAM AUTO_INCREMENT=230810 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_LIBRARY_SHELF`
--

DROP TABLE IF EXISTS `USER_LIBRARY_SHELF`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_LIBRARY_SHELF` (
  `LIBSHELFID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LIB` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `UID` int(10) unsigned NOT NULL,
  `DATE_CREATION` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`LIBSHELFID`),
  UNIQUE KEY `U_LIB` (`LIB`,`UID`),
  KEY `IDX_UID` (`UID`)
) ENGINE=MyISAM AUTO_INCREMENT=14241 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_MERGE_LOG`
--

DROP TABLE IF EXISTS `USER_MERGE_LOG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_MERGE_LOG` (
  `MERGEID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UID_OPERATOR` int(10) unsigned NOT NULL,
  `UID_FROM` int(10) unsigned NOT NULL,
  `UID_TO` int(10) unsigned NOT NULL,
  `DATELOG` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`MERGEID`)
) ENGINE=MyISAM AUTO_INCREMENT=733 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_MERGE_TOKEN`
--

DROP TABLE IF EXISTS `USER_MERGE_TOKEN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_MERGE_TOKEN` (
  `TID` int(11) NOT NULL AUTO_INCREMENT,
  `UIDFROM` int(11) NOT NULL,
  `UIDTO` int(11) NOT NULL,
  `TOKEN` varchar(40) NOT NULL,
  `DATE_CREATION` date NOT NULL,
  PRIMARY KEY (`TID`)
) ENGINE=InnoDB AUTO_INCREMENT=731 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_MODER_MSG`
--

DROP TABLE IF EXISTS `USER_MODER_MSG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_MODER_MSG` (
  `MESSAGEID` int(11) NOT NULL AUTO_INCREMENT,
  `UID` int(11) NOT NULL,
  `TITLE` varchar(255) NOT NULL,
  `MESSAGE` text NOT NULL,
  PRIMARY KEY (`MESSAGEID`),
  KEY `IDX_UID` (`UID`)
) ENGINE=MyISAM AUTO_INCREMENT=521 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_MODER_TMP`
--

DROP TABLE IF EXISTS `USER_MODER_TMP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_MODER_TMP` (
  `DOCID` int(11) NOT NULL,
  `UID` int(11) NOT NULL,
  `IP` int(11) unsigned NOT NULL,
  `ACTION` varchar(255) NOT NULL,
  `DATEMODER` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`DOCID`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_PREF_DEPOT`
--

DROP TABLE IF EXISTS `USER_PREF_DEPOT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_PREF_DEPOT` (
  `PREFID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `UID` int(11) unsigned NOT NULL,
  `PREF` varchar(255) NOT NULL,
  `VALUE` varchar(255) NOT NULL,
  PRIMARY KEY (`PREFID`),
  KEY `UID` (`UID`),
  KEY `PREF` (`PREF`)
) ENGINE=MyISAM AUTO_INCREMENT=806920 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_PREF_MAIL`
--

DROP TABLE IF EXISTS `USER_PREF_MAIL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_PREF_MAIL` (
  `PREFMID` int(25) NOT NULL AUTO_INCREMENT,
  `UID` int(25) NOT NULL,
  `RIGHTID` varchar(20) NOT NULL,
  `STRUCTID` int(25) DEFAULT NULL,
  `SEND` tinyint(1) NOT NULL,
  PRIMARY KEY (`PREFMID`)
) ENGINE=MyISAM AUTO_INCREMENT=10209 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_RIGHT`
--

DROP TABLE IF EXISTS `USER_RIGHT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_RIGHT` (
  `USRIGHTID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UID` int(10) unsigned NOT NULL,
  `SID` int(10) unsigned NOT NULL,
  `RIGHTID` varchar(20) NOT NULL,
  `VALUE` varchar(500) NOT NULL,
  PRIMARY KEY (`USRIGHTID`),
  KEY `IDX_SID` (`SID`),
  KEY `IDX_RIGHT` (`RIGHTID`),
  KEY `UID` (`UID`),
  KEY `URV` (`VALUE`(300))
) ENGINE=MyISAM AUTO_INCREMENT=151620 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_SEARCH`
--

DROP TABLE IF EXISTS `USER_SEARCH`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_SEARCH` (
  `SEARCHID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UID` int(10) unsigned NOT NULL,
  `LIB` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `URL` varchar(2000) COLLATE utf8_unicode_ci NOT NULL,
  `URL_API` varchar(2000) COLLATE utf8_unicode_ci NOT NULL,
  `FREQ` enum('none','day','week','month','push') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `SID` int(10) unsigned NOT NULL,
  `UPDATE_DATE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`SEARCHID`),
  KEY `IDX_UID` (`UID`),
  KEY `IDX_FREQ` (`FREQ`),
  KEY `SID` (`SID`)
) ENGINE=MyISAM AUTO_INCREMENT=25837 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_STAT_QUERIES`
--

DROP TABLE IF EXISTS `USER_STAT_QUERIES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_STAT_QUERIES` (
  `QUERYID` int(11) NOT NULL AUTO_INCREMENT,
  `UID` int(11) NOT NULL,
  `SPACE` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LABEL` varchar(2000) COLLATE utf8_unicode_ci NOT NULL,
  `FILTERS` text COLLATE utf8_unicode_ci,
  `FACET` varchar(2000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `PIVOT` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SORT` enum('count','index') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'count',
  `CUMUL` tinyint(4) NOT NULL DEFAULT '0',
  `ADDITIONAL` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CATEGORY` enum('repartition','consultation','ressource','provenance') COLLATE utf8_unicode_ci NOT NULL,
  `CHART` enum('PieChart','ColumnChart','BarChart','LineChart','SteppedAreaChart','GeoChart') COLLATE utf8_unicode_ci NOT NULL,
  `INTERVAL` enum('month','year') COLLATE utf8_unicode_ci DEFAULT 'month',
  `DATE_START` date DEFAULT NULL,
  `DATE_END` date DEFAULT NULL,
  `TYPE` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VIEW` enum('country','domain') COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`QUERYID`),
  KEY `UID` (`UID`)
) ENGINE=MyISAM AUTO_INCREMENT=1135 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_VALIDATE_MSG`
--

DROP TABLE IF EXISTS `USER_VALIDATE_MSG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_VALIDATE_MSG` (
  `MESSAGEID` int(11) NOT NULL AUTO_INCREMENT,
  `UID` int(11) NOT NULL,
  `TITLE` varchar(255) NOT NULL,
  `MESSAGE` text NOT NULL,
  PRIMARY KEY (`MESSAGEID`),
  KEY `IDX_UID` (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `VISITEURS`
--

DROP TABLE IF EXISTS `VISITEURS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `VISITEURS` (
  `APPID` int(11) unsigned NOT NULL,
  `IP` int(11) unsigned NOT NULL,
  `UID` int(11) unsigned NOT NULL,
  `DHIT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`APPID`,`IP`),
  KEY `IDX_APPID` (`APPID`),
  KEY `DHIT` (`DHIT`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COMMENT='Locale Visitors Logs';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `V_CONCAT_USERS_RIGHTS`
--

DROP TABLE IF EXISTS `V_CONCAT_USERS_RIGHTS`;
/*!50001 DROP VIEW IF EXISTS `V_CONCAT_USERS_RIGHTS`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `V_CONCAT_USERS_RIGHTS` AS SELECT 
 1 AS `USER`,
 1 AS `UserID`,
 1 AS `SID`,
 1 AS `ROLE`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `V_HIGHER_VALID_USERS_RIGHTS`
--

DROP TABLE IF EXISTS `V_HIGHER_VALID_USERS_RIGHTS`;
/*!50001 DROP VIEW IF EXISTS `V_HIGHER_VALID_USERS_RIGHTS`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `V_HIGHER_VALID_USERS_RIGHTS` AS SELECT 
 1 AS `USER`,
 1 AS `UserID`,
 1 AS `SID`,
 1 AS `ROLE`,
 1 AS `status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `V_VALID_USERS_RIGHTS`
--

DROP TABLE IF EXISTS `V_VALID_USERS_RIGHTS`;
/*!50001 DROP VIEW IF EXISTS `V_VALID_USERS_RIGHTS`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `V_VALID_USERS_RIGHTS` AS SELECT 
 1 AS `USER`,
 1 AS `UserID`,
 1 AS `SID`,
 1 AS `ROLE`,
 1 AS `status`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `WEBSITE`
--

DROP TABLE IF EXISTS `WEBSITE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEBSITE` (
  `SID` int(11) NOT NULL AUTO_INCREMENT,
  `TYPE` enum('PORTAIL','COLLECTION') NOT NULL,
  `SITE` varchar(255) NOT NULL,
  PRIMARY KEY (`SID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEBSITE_FOOTER`
--

DROP TABLE IF EXISTS `WEBSITE_FOOTER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEBSITE_FOOTER` (
  `SID` int(11) NOT NULL,
  `TYPE` enum('default','custom') NOT NULL DEFAULT 'default',
  `CONTENT` text NOT NULL,
  PRIMARY KEY (`SID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEBSITE_HEADER`
--

DROP TABLE IF EXISTS `WEBSITE_HEADER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEBSITE_HEADER` (
  `LOGOID` int(11) NOT NULL AUTO_INCREMENT,
  `SID` int(11) NOT NULL,
  `TYPE` enum('img','text') NOT NULL,
  `IMG` varchar(255) NOT NULL,
  `IMG_WIDTH` varchar(255) NOT NULL,
  `IMG_HEIGHT` varchar(255) NOT NULL,
  `IMG_HREF` varchar(255) NOT NULL,
  `IMG_ALT` varchar(255) NOT NULL,
  `TEXT` varchar(1000) NOT NULL,
  `TEXT_CLASS` varchar(255) NOT NULL,
  `TEXT_STYLE` varchar(255) NOT NULL,
  `ALIGN` varchar(10) NOT NULL,
  PRIMARY KEY (`LOGOID`,`SID`),
  KEY `IDX_SID` (`SID`)
) ENGINE=MyISAM AUTO_INCREMENT=111635 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEBSITE_NAVIGATION`
--

DROP TABLE IF EXISTS `WEBSITE_NAVIGATION`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEBSITE_NAVIGATION` (
  `NAVIGATIONID` int(11) NOT NULL AUTO_INCREMENT,
  `SID` int(11) NOT NULL,
  `PAGEID` int(11) NOT NULL,
  `TYPE_PAGE` varchar(255) NOT NULL,
  `CONTROLLER` varchar(255) NOT NULL,
  `ACTION` varchar(255) NOT NULL,
  `LABEL` varchar(500) NOT NULL,
  `PARENT_PAGEID` int(11) NOT NULL,
  `PARAMS` text NOT NULL,
  PRIMARY KEY (`NAVIGATIONID`),
  KEY `SID` (`SID`),
  KEY `TYPE_PAGE` (`TYPE_PAGE`),
  KEY `ACTION` (`ACTION`)
) ENGINE=MyISAM AUTO_INCREMENT=740275 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEBSITE_SETTINGS`
--

DROP TABLE IF EXISTS `WEBSITE_SETTINGS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEBSITE_SETTINGS` (
  `SID` int(11) unsigned NOT NULL,
  `SETTING` varchar(50) NOT NULL,
  `VALUE` varchar(1000) NOT NULL,
  PRIMARY KEY (`SID`,`SETTING`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEBSITE_STYLES`
--

DROP TABLE IF EXISTS `WEBSITE_STYLES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEBSITE_STYLES` (
  `SID` int(11) NOT NULL,
  `SETTING` varchar(50) NOT NULL,
  `VALUE` varchar(1000) NOT NULL,
  PRIMARY KEY (`SID`,`SETTING`),
  KEY `SID_IDX` (`SID`),
  KEY `SETTING` (`SETTING`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `V_CONCAT_USERS_RIGHTS`
--

/*!50001 DROP VIEW IF EXISTS `V_CONCAT_USERS_RIGHTS`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`%.in2p3.fr` SQL SECURITY DEFINER */
/*!50001 VIEW `V_CONCAT_USERS_RIGHTS` AS select `CAS_users`.`T_UTILISATEURS`.`USERNAME` AS `USER`,`CAS_users`.`T_UTILISATEURS`.`UID` AS `UserID`,`HALV3`.`USER_RIGHT`.`SID` AS `SID`,group_concat(`HALV3`.`USER_RIGHT`.`RIGHTID` separator ',') AS `ROLE` from ((`HALV3`.`USER` join `CAS_users`.`T_UTILISATEURS` on((`HALV3`.`USER`.`UID` = `CAS_users`.`T_UTILISATEURS`.`UID`))) left join `HALV3`.`USER_RIGHT` on((`HALV3`.`USER`.`UID` = `HALV3`.`USER_RIGHT`.`UID`))) group by `CAS_users`.`T_UTILISATEURS`.`UID` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `V_HIGHER_VALID_USERS_RIGHTS`
--

/*!50001 DROP VIEW IF EXISTS `V_HIGHER_VALID_USERS_RIGHTS`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`%.in2p3.fr` SQL SECURITY DEFINER */
/*!50001 VIEW `V_HIGHER_VALID_USERS_RIGHTS` AS select `V_UTILISATEURS_VALIDES`.`USERNAME` AS `USER`,`V_UTILISATEURS_VALIDES`.`UID` AS `UserID`,`HALV3`.`USER_RIGHT`.`SID` AS `SID`,`HALV3`.`USER_RIGHT`.`RIGHTID` AS `ROLE`,min((case when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'administrator') then 1 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'adminstruct') then 3 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'haladmin') then 0 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'halmsadmin') then 2 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'moderateur') then 1 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'tamponneur') then 4 else 5 end)) AS `status` from (`CAS_users`.`V_UTILISATEURS_VALIDES` left join `HALV3`.`USER_RIGHT` on((`V_UTILISATEURS_VALIDES`.`UID` = `HALV3`.`USER_RIGHT`.`UID`))) group by `V_UTILISATEURS_VALIDES`.`UID` order by `V_UTILISATEURS_VALIDES`.`UID`,`status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `V_VALID_USERS_RIGHTS`
--

/*!50001 DROP VIEW IF EXISTS `V_VALID_USERS_RIGHTS`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`%.in2p3.fr` SQL SECURITY DEFINER */
/*!50001 VIEW `V_VALID_USERS_RIGHTS` AS select `V_UTILISATEURS_VALIDES`.`USERNAME` AS `USER`,`V_UTILISATEURS_VALIDES`.`UID` AS `UserID`,`HALV3`.`USER_RIGHT`.`SID` AS `SID`,`HALV3`.`USER_RIGHT`.`RIGHTID` AS `ROLE`,(case when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'administrator') then 1 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'adminstruct') then 3 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'haladmin') then 0 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'halmsadmin') then 2 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'moderateur') then 1 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'tamponneur') then 4 else 5 end) AS `status` from (`CAS_users`.`V_UTILISATEURS_VALIDES` left join `HALV3`.`USER_RIGHT` on((`V_UTILISATEURS_VALIDES`.`UID` = `HALV3`.`USER_RIGHT`.`UID`))) order by `V_UTILISATEURS_VALIDES`.`UID`,(case when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'administrator') then 1 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'adminstruct') then 3 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'haladmin') then 0 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'halmsadmin') then 2 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'moderateur') then 1 when (`HALV3`.`USER_RIGHT`.`RIGHTID` = 'tamponneur') then 4 else 5 end) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-11-19 14:05:19
