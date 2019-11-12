-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Version du serveur :  5.6.20-log
-- Version de PHP :  5.5.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `CAS_users`
--
CREATE DATABASE IF NOT EXISTS `CAS_users` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `CAS_users`;

-- --------------------------------------------------------

--
-- Structure de la table `FTP_GROUP`
--

CREATE TABLE `FTP_GROUP` (
  `groupname` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `gid` smallint(6) NOT NULL DEFAULT '99',
  `members` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table des groupes ProFTPD';

-- --------------------------------------------------------

--
-- Structure de la table `FTP_QUOTA_LIMITS`
--

CREATE TABLE `FTP_QUOTA_LIMITS` (
  `Id` int(11) UNSIGNED NOT NULL,
  `username` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `quota_type` enum('user','group','class','all') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `par_session` enum('false','true') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'false',
  `limit_type` enum('soft','hard') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'soft',
  `bytes_up_limit` float NOT NULL DEFAULT '0',
  `bytes_down_limit` float NOT NULL DEFAULT '0',
  `bytes_transfer_limit` float NOT NULL DEFAULT '0',
  `files_up_limit` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `files_down_limit` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `files_transfer_limit` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table des quotas ProFTPD';

-- --------------------------------------------------------

--
-- Structure de la table `FTP_QUOTA_TOTAL`
--

CREATE TABLE `FTP_QUOTA_TOTAL` (
  `username` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `quota_type` enum('user','group','class','all') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `bytes_up_total` float NOT NULL DEFAULT '0',
  `bytes_down_total` float NOT NULL DEFAULT '0',
  `bytes_transfer_total` float NOT NULL DEFAULT '0',
  `files_up_total` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `files_down_total` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `files_transfer_total` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table des compteurs des quotas ProFTPD';

-- --------------------------------------------------------

--
-- Structure de la table `SU_LOG`
--

CREATE TABLE `SU_LOG` (
  `ID` int(10) UNSIGNED NOT NULL,
  `FROM_UID` int(10) UNSIGNED NOT NULL,
  `TO_UID` int(10) UNSIGNED NOT NULL,
  `APPLICATION` varchar(50) CHARACTER SET utf8 NOT NULL,
  `ACTION` enum('GRANTED','DENIED') DEFAULT NULL,
  `SU_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `T_UTILISATEURS`
--

CREATE TABLE `T_UTILISATEURS` (
  `UID` int(11) UNSIGNED NOT NULL,
  `USERNAME` varchar(100) NOT NULL,
  `PASSWORD` varchar(128) NOT NULL,
  `EMAIL` varchar(320) NOT NULL COMMENT 'http://tools.ietf.org/html/rfc3696#section-3',
  `CIV` varchar(255) DEFAULT NULL,
  `LASTNAME` varchar(100) NOT NULL,
  `FIRSTNAME` varchar(100) DEFAULT NULL,
  `MIDDLENAME` varchar(100) DEFAULT NULL,
  `TIME_REGISTERED` timestamp NULL DEFAULT NULL COMMENT 'Date création du compte',
  `TIME_MODIFIED` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date modification du compte',
  `PHOTO` mediumblob,
  `FTP_HOME` varchar(255) DEFAULT NULL COMMENT 'Chemin du home FTP',
  `FTP_LAST_AUTH` datetime DEFAULT NULL COMMENT 'Dernière authentification par FTP',
  `FTP_LAST_USE` datetime DEFAULT NULL COMMENT 'Dernière utilisation du FTP',
  `VALID` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Comptes utilisateurs pour CAS';

-- --------------------------------------------------------

--
-- Structure de la table `T_UTILISATEURS_TOKENS`
--

CREATE TABLE `T_UTILISATEURS_TOKENS` (
  `UID` int(10) UNSIGNED NOT NULL,
  `EMAIL` varchar(100) CHARACTER SET utf8 NOT NULL COMMENT 'E-mail auquel le jeton est envoyé',
  `TOKEN` varchar(40) CHARACTER SET utf8 NOT NULL COMMENT 'Jeton à usage unique',
  `TIME_MODIFIED` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `USAGE` set('VALID','PASSWORD') COLLATE utf8_unicode_ci NOT NULL COMMENT 'Jeton pour mot de passe perdu ou validation de compte'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `V_UTILISATEURS_VALIDES`
--
CREATE TABLE `V_UTILISATEURS_VALIDES` (
`UID` int(11) unsigned
,`USERNAME` varchar(100)
,`PASSWORD` varchar(128)
,`EMAIL` varchar(320)
,`CIV` varchar(255)
,`LASTNAME` varchar(100)
,`FIRSTNAME` varchar(100)
,`MIDDLENAME` varchar(100)
);

-- --------------------------------------------------------

--
-- Structure de la vue `V_UTILISATEURS_VALIDES`
--
DROP TABLE IF EXISTS `V_UTILISATEURS_VALIDES`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%.in2p3.fr` SQL SECURITY DEFINER VIEW `V_UTILISATEURS_VALIDES`  AS  select `T_UTILISATEURS`.`UID` AS `UID`,`T_UTILISATEURS`.`USERNAME` AS `USERNAME`,`T_UTILISATEURS`.`PASSWORD` AS `PASSWORD`,`T_UTILISATEURS`.`EMAIL` AS `EMAIL`,`T_UTILISATEURS`.`CIV` AS `CIV`,`T_UTILISATEURS`.`LASTNAME` AS `LASTNAME`,`T_UTILISATEURS`.`FIRSTNAME` AS `FIRSTNAME`,`T_UTILISATEURS`.`MIDDLENAME` AS `MIDDLENAME` from `T_UTILISATEURS` where (`T_UTILISATEURS`.`VALID` = 1) ;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `FTP_GROUP`
--
ALTER TABLE `FTP_GROUP`
  ADD KEY `groupname` (`groupname`);

--
-- Index pour la table `FTP_QUOTA_LIMITS`
--
ALTER TABLE `FTP_QUOTA_LIMITS`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `FTP_QUOTA_TOTAL`
--
ALTER TABLE `FTP_QUOTA_TOTAL`
  ADD PRIMARY KEY (`username`,`quota_type`);

--
-- Index pour la table `SU_LOG`
--
ALTER TABLE `SU_LOG`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FROM_UID` (`FROM_UID`,`TO_UID`,`APPLICATION`),
  ADD KEY `ACTION` (`ACTION`);

--
-- Index pour la table `T_UTILISATEURS`
--
ALTER TABLE `T_UTILISATEURS`
  ADD PRIMARY KEY (`UID`),
  ADD UNIQUE KEY `U_USERNAME` (`USERNAME`),
  ADD KEY `PASSWORD` (`PASSWORD`),
  ADD KEY `EMAIL` (`EMAIL`),
  ADD KEY `VALID` (`VALID`),
  ADD KEY `FIRSTNAME` (`FIRSTNAME`),
  ADD KEY `LASTNAME` (`LASTNAME`);

--
-- Index pour la table `T_UTILISATEURS_TOKENS`
--
ALTER TABLE `T_UTILISATEURS_TOKENS`
  ADD PRIMARY KEY (`EMAIL`,`TOKEN`),
  ADD UNIQUE KEY `TOKEN` (`TOKEN`),
  ADD KEY `USAGE` (`USAGE`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `FTP_QUOTA_LIMITS`
--
ALTER TABLE `FTP_QUOTA_LIMITS`
  MODIFY `Id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324592;
--
-- AUTO_INCREMENT pour la table `SU_LOG`
--
ALTER TABLE `SU_LOG`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=681;
--
-- AUTO_INCREMENT pour la table `T_UTILISATEURS`
--
ALTER TABLE `T_UTILISATEURS`
  MODIFY `UID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=336607;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
