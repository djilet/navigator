SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `college_list` (
  `ListID` int(10) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `StaticPath` varchar(255) DEFAULT NULL,
  `MetaTitle` varchar(255) DEFAULT NULL,
  `MetaDescription` varchar(255) DEFAULT NULL,
  `Public` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`ListID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

INSERT INTO `college_list` (`ListID`, `Title`, `Description`, `StaticPath`, `MetaTitle`, `MetaDescription`, `Public`) VALUES
(1, 'Колледжи спб', '', 'kolledzhi-spb', '', '', 'Y'),
(2, 'Колледжи после 9 класса', '', 'kolledzhi-posle-9-klassa', '', '', 'Y'),
(3, 'Медицинский колледж', '', 'meditsinskiy-kolledzh', '', '', 'Y'),
(4, 'Педагогический колледж', '', 'pedagogicheskiy-kolledzh', '', '', 'Y'),
(5, 'Политехнические колледжи', '', 'politekhnicheskie-kolledzhi', '', '', 'Y'),
(6, 'Экономические колледжи', '', 'ekonomicheskie-kolledzhi', '', '', 'Y'),
(7, 'Колледжи культуры', '', 'kolledzhi-kultury', '', '', 'Y');

CREATE TABLE IF NOT EXISTS `college_list_filter` (
  `ListID` int(10) NOT NULL,
  `FilterName` varchar(255) NOT NULL,
  `FilterValue` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `college_list_filter` (`ListID`, `FilterName`, `FilterValue`) VALUES
(1, 'Region', '["13"]'),
(2, 'AdmissionBase', '["2"]'),
(3, 'CollegeBigDirection', '["1"]'),
(4, 'CollegeBigDirection', '["9"]'),
(5, 'CollegeBigDirection', '["3"]'),
(6, 'CollegeBigDirection', '["6"]'),
(7, 'CollegeBigDirection', '["8"]');

