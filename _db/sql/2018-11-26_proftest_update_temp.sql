-- Character
CREATE TABLE IF NOT EXISTS `proftest_character` (
  `CharacterID` int(5) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `SortOrder` int(10) DEFAULT NULL,
  PRIMARY KEY (`CharacterID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- CharacterID For Task
ALTER TABLE `proftest_task` ADD `CharacterID` INT(5) NOT NULL AFTER `ProftestID`;

-- Points for answer
ALTER TABLE `proftest_answer` ADD `Points` INT(10) NOT NULL AFTER `TaskID`;
