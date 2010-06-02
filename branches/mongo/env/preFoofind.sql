CREATE TABLE `ff_file` (
  `IdFile` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `Size` bigint(8) unsigned DEFAULT NULL,
  `FirstSeenDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `LastSeenDate` datetime DEFAULT NULL,
  `ContentType` int(2) NOT NULL,
  `blocked` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`IdFile`)
) ENGINE=MyISAM;

CREATE TABLE `ff_filename` (
  `IdFilename` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `IdFile` int(4) unsigned NOT NULL,
  `Filename` varchar(255) NOT NULL,
  `CrcFilename` int(4) unsigned NOT NULL,
  `Extension` varchar(10) NOT NULL,
  `CrcExtension` int(4) unsigned NOT NULL,
  `LastSources` int(4) unsigned NOT NULL DEFAULT '1',
  `MaxSources` int(4) unsigned NOT NULL DEFAULT '1',
  `idSource` int(4) NOT NULL,
  PRIMARY KEY (`IdFilename`),
  UNIQUE KEY `IdFileFilenameKey` (`IdFile`,`CrcFilename`),
  KEY `FilenameKey` (`Filename`)
) ENGINE=MyISAM;

CREATE TABLE `ff_sources` (
  `IdFile` int(4) unsigned NOT NULL,
  `Type` int(2) unsigned NOT NULL,
  `Uri` varchar(255) NOT NULL,
  `CrcUri` int(4) unsigned NOT NULL,
  `Sources` int(4) unsigned DEFAULT NULL,
  `MaxSources` int(4) unsigned DEFAULT NULL,
  `idFilename` int(4) unsigned DEFAULT NULL,
  PRIMARY KEY (`IdFile`,`Type`,`CrcUri`),
  UNIQUE KEY `TypeUriKey` (`Type`,`Uri`)
) ENGINE=MyISAM;

CREATE TABLE `ff_metadata` (
  `IdFile` int(4) unsigned NOT NULL,
  `KeyMD` varchar(100) NOT NULL,
  `ValueMD` varchar(255) NOT NULL,
  `CrcKey` int(4) unsigned NOT NULL,
  `Encoded` int(4) unsigned NULL,
  PRIMARY KEY (`IdFile`,`CrcKey`)
) ENGINE=MyISAM;


