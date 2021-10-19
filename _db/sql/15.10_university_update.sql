ALTER TABLE `data_university` ADD `WhyChoose` TEXT NULL DEFAULT NULL AFTER `Content`;

CREATE TABLE IF NOT EXISTS `data_achievement` (
  `AchievementID` int(10) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `SortOrder` int(10) DEFAULT '0',
  PRIMARY KEY (`AchievementID`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

INSERT INTO `data_achievement` (`AchievementID`, `Title`, `SortOrder`) VALUES
(1, 'Золотая медаль', 0),
(2, 'Значок ГТО', 0),
(3, 'Перечневая олимпиада', 9999),
(4, 'Волонтерство', 0),
(5, 'Итоговое сочинение', 0);

CREATE TABLE IF NOT EXISTS `data_speciality2achievement` (
  `SpecialityID` int(10) NOT NULL,
  `AchievementID` int(10) NOT NULL,
  `Score` int(5) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;