CREATE TABLE `user_tracking` (
  `ID` int(255) NOT NULL,
  `Created` datetime NOT NULL,
  `UserID` int(10) DEFAULT NULL,
  `TrackID` varchar(32) DEFAULT NULL,
  `Page` varchar(255) NOT NULL,
  `Action` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


ALTER TABLE `user_tracking`
  ADD PRIMARY KEY (`ID`);


ALTER TABLE `user_tracking`
  MODIFY `ID` int(255) NOT NULL AUTO_INCREMENT;

