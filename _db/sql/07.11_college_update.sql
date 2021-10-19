--
-- Dumping data for table `page`
--
INSERT INTO `page` (`WebsiteID`, `LanguageCode`, `Path2Root`, `MenuImage1`, `MenuImage1Config`, `Title`, `Description`, `TitleH1`, `MetaTitle`, `MetaKeywords`, `MetaDescription`, `StaticPath`, `Content`, `Template`, `Created`, `Modified`, `Active`, `Type`, `Link`, `Config`, `Target`) VALUES
(1, 'ru', '#38#', NULL, '{"Width":0,"Height":0}', 'Колледжи', 'Description=&Description2=', '', '', '', '', 'college', '', '', '2018-11-01 12:16:43', '2018-11-01 12:17:08', 'Y', 2, 'college', '', '');

-- Types for question_message
ALTER TABLE `question_message` CHANGE `Type` `Type` ENUM('university','article','speciality','college','collegeSpeciality') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

CREATE TABLE `college_award` (
  `AwardsID` int(10) NOT NULL,
  `Title` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `college_bigdirection` (
  `CollegeBigDirectionID` int(10) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `SortOrder` int(5) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `college_college` (
  `CollegeID` int(10) NOT NULL,
  `ImportID` int(10) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `ShortTitle` varchar(255) DEFAULT NULL,
  `StaticPath` varchar(150) DEFAULT NULL,
  `RegionID` int(10) DEFAULT NULL,
  `City` varchar(255) DEFAULT NULL,
  `Type` enum('State','NotState') DEFAULT NULL,
  `AtUniversity` enum('Y','N') DEFAULT NULL,
  `CollegeBigDirectionID` int(10) DEFAULT NULL,
  `Website` varchar(255) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Latitude` varchar(255) DEFAULT NULL,
  `Longitude` varchar(255) DEFAULT NULL,
  `PhoneSelectionCommittee` varchar(150) DEFAULT NULL,
  `AccreditationValidity` varchar(255) DEFAULT NULL,
  `Achievements` mediumtext,
  `Hostel` varchar(255) DEFAULT NULL,
  `HostelPrice` varchar(255) DEFAULT NULL,
  `Scholarship` varchar(50) DEFAULT NULL,
  `ScholarshipSocial` varchar(50) DEFAULT NULL,
  `VideoURL` varchar(255) DEFAULT NULL,
  `SortOrder` int(5) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `college_college2award` (
  `CollegeID` int(10) DEFAULT NULL,
  `AwardsID` int(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `college_image` (
  `ImageID` int(10) NOT NULL,
  `CollegeID` int(10) NOT NULL,
  `ItemImage` varchar(255) NOT NULL,
  `SortOrder` int(5) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `college_speciality` (
  `CollegeSpecialityID` int(10) NOT NULL,
  `ImportID` int(10) DEFAULT NULL,
  `CollegeID` int(10) DEFAULT NULL,
  `CollegeBigDirectionID` int(10) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `StaticPath` varchar(255) DEFAULT NULL,
  `AdmissionBase` varchar(255) DEFAULT NULL,
  `GPA` varchar(50) DEFAULT NULL,
  `FullStudBudgetCount` varchar(50) DEFAULT NULL,
  `FullStudPaidCount` varchar(50) DEFAULT NULL,
  `FullStudPeriod` varchar(255) DEFAULT NULL,
  `FullStudPaidPrice` varchar(50) DEFAULT NULL,
  `PartStudBudgetCount` varchar(50) DEFAULT NULL,
  `PartStudPaidCount` varchar(50) DEFAULT NULL,
  `PartStudPeriod` varchar(255) DEFAULT NULL,
  `PartStudPaidPrice` varchar(50) DEFAULT NULL,
  `ExtramuralStudBudgetCount` varchar(50) DEFAULT NULL,
  `ExtramuralStudPaidCount` varchar(50) DEFAULT NULL,
  `ExtramuralStudPeriod` varchar(255) DEFAULT NULL,
  `ExtramuralStudPaidPrice` varchar(50) DEFAULT NULL,
  `RemoteStudBudgetCount` varchar(50) DEFAULT NULL,
  `RemoteStudPaidCount` varchar(50) DEFAULT NULL,
  `RemoteStudPeriod` varchar(50) DEFAULT NULL,
  `RemoteStudPaidPrice` varchar(255) DEFAULT NULL,
  `Address1` varchar(255) DEFAULT NULL,
  `Address2` varchar(255) DEFAULT NULL,
  `Address3` varchar(255) DEFAULT NULL,
  `Address4` varchar(255) DEFAULT NULL,
  `Latitude1` varchar(255) DEFAULT NULL,
  `Longitude1` varchar(255) DEFAULT NULL,
  `Latitude2` varchar(255) DEFAULT NULL,
  `Longitude2` varchar(255) DEFAULT NULL,
  `Latitude3` varchar(255) DEFAULT NULL,
  `Longitude3` varchar(255) DEFAULT NULL,
  `Latitude4` varchar(255) DEFAULT NULL,
  `Longitude4` varchar(255) DEFAULT NULL,
  `Qualification` text,
  `SortOrder` int(5) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


ALTER TABLE `college_award`
  ADD PRIMARY KEY (`AwardsID`);

ALTER TABLE `college_bigdirection`
  ADD PRIMARY KEY (`CollegeBigDirectionID`);

ALTER TABLE `college_college`
  ADD PRIMARY KEY (`CollegeID`);

ALTER TABLE `college_image`
  ADD PRIMARY KEY (`ImageID`);

ALTER TABLE `college_speciality`
  ADD PRIMARY KEY (`CollegeSpecialityID`);


ALTER TABLE `college_award`
  MODIFY `AwardsID` int(10) NOT NULL AUTO_INCREMENT;
ALTER TABLE `college_bigdirection`
  MODIFY `CollegeBigDirectionID` int(10) NOT NULL AUTO_INCREMENT;
ALTER TABLE `college_college`
  MODIFY `CollegeID` int(10) NOT NULL AUTO_INCREMENT;
ALTER TABLE `college_image`
  MODIFY `ImageID` int(10) NOT NULL AUTO_INCREMENT;
ALTER TABLE `college_speciality`
  MODIFY `CollegeSpecialityID` int(10) NOT NULL AUTO_INCREMENT;
