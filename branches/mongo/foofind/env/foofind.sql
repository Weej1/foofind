CREATE TABLE `ff_file` (
  `IdFile` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `Size` bigint(8) unsigned DEFAULT NULL,
  `FirstSeenDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `LastSeenDate` datetime DEFAULT NULL,
  `ContentType` int(2) NOT NULL,
  `blocked` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`IdFile`)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

CREATE TABLE `ff_metadata` (
  `IdFile` int(4) unsigned NOT NULL,
  `KeyMD` varchar(100) NOT NULL,
  `ValueMD` varchar(255) NOT NULL,
  `CrcKey` int(4) unsigned NOT NULL,
  `Encoded` int(4) unsigned DEFAULT NULL,
  PRIMARY KEY (`IdFile`,`CrcKey`)
) ENGINE=InnoDB;

CREATE TABLE `ff_metadata_encoded` (
  `CrcKey` int(4) unsigned NOT NULL,
  `Count` int(4) unsigned NOT NULL,
  `IdEncoded` int(2) unsigned NULL,
  PRIMARY KEY (`CrcKey`)
) DEFAULT CHARSET=utf8;

drop function ff_get_encoded;
delimiter //
CREATE FUNCTION ff_get_encoded(pcrckey int(4) unsigned, pvalueMD varchar(255)) RETURNS int(4) unsigned
BEGIN
	DECLARE rid int(2) unsigned;
	DECLARE val int unsigned;
	SET rid = 32767;
	SELECT IdEncoded INTO rid FROM ff_metadata_encoded WHERE crcKey = pcrckey LIMIT 1;
	IF rid = 0 THEN
		RETURN 1;
	ELSEIF rid = 32767 THEN 
		RETURN NULL; 
	ELSE
		select parse_int(pvalueMD) into val;
		
		IF pcrckey = 2783024982 or pcrckey = 3989296854 THEN
			if NOT val BETWEEN 1900 AND year(now())+1 THEN SET val = NULL; END IF;
		END IF;
		RETURN rid<<22 | val;
	END IF;
END//
delimiter ;

select ff_get_encoded(3671651427, '342wsda');

delimiter //
CREATE TRIGGER ff_metadata_encoder BEFORE INSERT ON ff_metadata
  FOR EACH ROW SET NEW.Encoded = ff_get_encoded(NEW.CrcKey, NEW.ValueMD)//
CREATE TRIGGER ff_metadata_encoder2 BEFORE UPDATE ON ff_metadata
  FOR EACH ROW BEGIN if NEW.ValueMD!=OLD.ValueMD THEN SET NEW.Encoded = ff_get_encoded(NEW.CrcKey, NEW.ValueMD); END IF; END//
CREATE TRIGGER ff_metadata_encoder2 BEFORE UPDATE ON ff_metadata
  FOR EACH ROW SET NEW.Encoded = ff_get_encoded(NEW.CrcKey, NEW.ValueMD)//
delimiter ;

update ff_metadata_encoded set count = 0;
insert into ff_metadata_encoded 
	select crckey, count(*) c, null from ff_metadata where idfile between 2000001 and 3000000 group by crcKey 
	on duplicate key update count=count+VALUES(count);

-- textos
update ff_metadata_encoded set idencoded = 0 WHERE crcKey in (3671651427, 295520833, 53697641, 4288146079, 2531033464);

-- años
update ff_metadata_encoded set idencoded = 1 WHERE crcKey in (2783024982, 3989296854);

-- calidad
update ff_metadata_encoded set idencoded = 2 WHERE crcKey in (4227390729);


