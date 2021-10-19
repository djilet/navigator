DROP TABLE IF EXISTS `data_exhibition_action`;
CREATE TABLE IF NOT EXISTS `data_exhibition_action` (
  `ActionID` int(10) unsigned NOT NULL,
  `RoomID` int(10) unsigned NOT NULL,
  `TimeFrom` varchar(5) NOT NULL,
  `TimeTo` varchar(5) NOT NULL,
  `Type` varchar(255) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Post` varchar(255) NOT NULL,
  `Description` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `data_exhibition_room`;
CREATE TABLE IF NOT EXISTS `data_exhibition_room` (
  `RoomID` int(10) unsigned NOT NULL,
  `CityID` int(10) unsigned NOT NULL,
  `Title` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `data_exhibition_action`
  ADD PRIMARY KEY (`ActionID`),
  ADD KEY `RoomID` (`RoomID`);

ALTER TABLE `data_exhibition_room`
  ADD PRIMARY KEY (`RoomID`),
  ADD KEY `CityID` (`CityID`);

ALTER TABLE `data_exhibition_action`
  MODIFY `ActionID` int(10) unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_exhibition_room`
  MODIFY `RoomID` int(10) unsigned NOT NULL AUTO_INCREMENT;
