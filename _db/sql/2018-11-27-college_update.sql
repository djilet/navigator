CREATE TABLE IF NOT EXISTS `college_admission_base` (
  `AdmissionBaseID` int(5) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `InFilter` enum('Y','N') NOT NULL DEFAULT 'Y',
  `SortOrder` int(5) DEFAULT NULL,
  PRIMARY KEY (`AdmissionBaseID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- AdmissionBase in college_speciality
ALTER TABLE `college_speciality` CHANGE `AdmissionBase` `AdmissionBaseID` INT(5) NULL DEFAULT NULL;

-- Logo
ALTER TABLE `college_college` ADD `CollegeLogo` VARCHAR(255) NULL DEFAULT NULL AFTER `Type`, ADD `CollegeLogoConfig` VARCHAR(255) NULL DEFAULT NULL AFTER `CollegeLogo`;