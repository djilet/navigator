DROP TABLE IF EXISTS `college_list`;
CREATE TABLE `college_list` (
  `ListID` int(10) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `StaticPath` varchar(255) DEFAULT NULL,
  `MetaTitle` varchar(255) DEFAULT NULL,
  `MetaDescription` varchar(255) DEFAULT NULL,
  `Public` enum('Y','N') NOT NULL DEFAULT 'Y',
  `SortOrder` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE TABLE `college_list`;
INSERT INTO `college_list` (`ListID`, `Title`, `Description`, `StaticPath`, `MetaTitle`, `MetaDescription`, `Public`, `SortOrder`) VALUES
(1, 'колледжи спб', '', 'kolledzhi-spb', '', '', 'Y', 2),
(2, 'колледжи после 9 класса', '', 'kolledzhi-posle-9-klassa', '', '', 'Y', 3),
(3, 'медицинский колледж', '', 'meditsinskiy-kolledzh', '', '', 'Y', 3),
(4, 'педагогический колледж', '', 'pedagogicheskiy-kolledzh', '', '', 'Y', 4),
(5, 'политехнические колледжи', '', 'politekhnicheskie-kolledzhi', '', '', 'Y', 5),
(6, 'экономические колледжи', '', 'ekonomicheskie-kolledzhi', '', '', 'Y', 6),
(7, 'колледжи культуры', '', 'kolledzhi-kultury', '', '', 'Y', 7),
(8, 'Колледжи Москвы', '', 'kolledzhi-moskvy', '', '', 'Y', 1);


ALTER TABLE `college_list`
  ADD PRIMARY KEY (`ListID`);


ALTER TABLE `college_list`
  MODIFY `ListID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
