--
-- Table structure for table `data_speciality_study`
--

CREATE TABLE IF NOT EXISTS `data_speciality_study` (
  `StudyId` int(10) NOT NULL AUTO_INCREMENT,
  `SpecialityID` int(10) NOT NULL,
  `Year` int(5) NOT NULL,
  `Type` enum('Full','Part','Extramural') DEFAULT NULL,
  `BudgetScopeWave1` varchar(50) DEFAULT NULL,
  `BudgetScopeWave2` varchar(50) DEFAULT NULL,
  `PaidScope` varchar(50) DEFAULT NULL,
  `BudgetCount` varchar(50) DEFAULT NULL,
  `PaidCount` varchar(50) DEFAULT NULL,
  `BudgetCompetition` varchar(50) DEFAULT NULL,
  `PaidCompetition` varchar(50) DEFAULT NULL,
  `Period` varchar(50) DEFAULT NULL,
  `PaidPrice` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`StudyId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
