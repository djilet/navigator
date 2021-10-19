-- phpMyAdmin SQL Dump
-- version 4.4.12
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 09, 2018 at 11:53 AM
-- Server version: 5.7.24-0ubuntu0.18.04.1
-- PHP Version: 7.2.10-0ubuntu0.18.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `navigator`
--

-- --------------------------------------------------------

--
-- Table structure for table `proftest_answer`
--

CREATE TABLE IF NOT EXISTS `proftest_answer` (
  `AnswerID` int(10) unsigned NOT NULL,
  `TaskID` int(10) unsigned NOT NULL,
  `SortOrder` int(5) unsigned NOT NULL,
  `Title` varchar(255) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `proftest_answer`
--

INSERT INTO `proftest_answer` (`AnswerID`, `TaskID`, `SortOrder`, `Title`) VALUES
(1, 1, 1, 'вариант 1'),
(2, 1, 2, 'вариант 2'),
(3, 1, 3, 'вариант 3'),
(4, 2, 1, 'вариант 2-1'),
(5, 2, 2, 'вариант 2-2'),
(6, 2, 3, 'вариант 2-3'),
(7, 2, 4, 'вариант 2-4'),
(8, 3, 1, 'вариант 3-1'),
(9, 3, 2, 'вариант 3-2');

-- --------------------------------------------------------

--
-- Table structure for table `proftest_answer2user`
--

CREATE TABLE IF NOT EXISTS `proftest_answer2user` (
  `ProftestUserID` int(10) unsigned NOT NULL,
  `TaskID` int(10) unsigned NOT NULL,
  `AnswerID` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proftest_item`
--

CREATE TABLE IF NOT EXISTS `proftest_item` (
  `ProftestID` int(10) unsigned NOT NULL,
  `PageID` int(10) unsigned DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `proftest_item`
--

INSERT INTO `proftest_item` (`ProftestID`, `PageID`) VALUES
(1, 134);

-- --------------------------------------------------------

--
-- Table structure for table `proftest_task`
--

CREATE TABLE IF NOT EXISTS `proftest_task` (
  `TaskID` int(10) unsigned NOT NULL,
  `ProftestID` int(10) unsigned NOT NULL,
  `SortOrder` int(5) unsigned NOT NULL,
  `Type` enum('radio','checkbox') NOT NULL,
  `Text` text NOT NULL,
  `AnswerCount` int(2) DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `proftest_task`
--

INSERT INTO `proftest_task` (`TaskID`, `ProftestID`, `SortOrder`, `Type`, `Text`, `AnswerCount`) VALUES
(1, 1, 1, 'checkbox', 'Первое задание тестового профтеста', 2),
(2, 1, 2, 'radio', 'Второе задание тестового профтеста', NULL),
(3, 1, 3, 'checkbox', 'вмываымва мваымва мывамываым\r\nамывамваы\r\nмваым вамыв', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `proftest_task2user`
--

CREATE TABLE IF NOT EXISTS `proftest_task2user` (
  `TaskSolutionID` int(10) unsigned NOT NULL,
  `ProftestUserID` int(10) unsigned NOT NULL,
  `TaskID` int(10) unsigned NOT NULL,
  `Status` enum('available','complete') NOT NULL DEFAULT 'available',
  `Completed` datetime DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proftest_user`
--

CREATE TABLE IF NOT EXISTS `proftest_user` (
  `ProftestUserID` int(10) unsigned NOT NULL,
  `UserID` int(10) unsigned DEFAULT NULL,
  `MarathonUserID` int(10) unsigned DEFAULT NULL,
  `Created` datetime NOT NULL,
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `proftest_answer`
--
ALTER TABLE `proftest_answer`
  ADD PRIMARY KEY (`AnswerID`),
  ADD KEY `TaskID` (`TaskID`);

--
-- Indexes for table `proftest_answer2user`
--
ALTER TABLE `proftest_answer2user`
  ADD KEY `ProftestUserID` (`ProftestUserID`),
  ADD KEY `AnswerID` (`AnswerID`),
  ADD KEY `TaskID` (`TaskID`);

--
-- Indexes for table `proftest_item`
--
ALTER TABLE `proftest_item`
  ADD PRIMARY KEY (`ProftestID`);

--
-- Indexes for table `proftest_task`
--
ALTER TABLE `proftest_task`
  ADD PRIMARY KEY (`TaskID`),
  ADD KEY `ProftestID` (`ProftestID`);

--
-- Indexes for table `proftest_task2user`
--
ALTER TABLE `proftest_task2user`
  ADD PRIMARY KEY (`TaskSolutionID`),
  ADD KEY `TaskID` (`TaskID`);

--
-- Indexes for table `proftest_user`
--
ALTER TABLE `proftest_user`
  ADD PRIMARY KEY (`ProftestUserID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `MarathonUserID` (`MarathonUserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `proftest_answer`
--
ALTER TABLE `proftest_answer`
  MODIFY `AnswerID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `proftest_item`
--
ALTER TABLE `proftest_item`
  MODIFY `ProftestID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `proftest_task`
--
ALTER TABLE `proftest_task`
  MODIFY `TaskID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `proftest_task2user`
--
ALTER TABLE `proftest_task2user`
  MODIFY `TaskSolutionID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `proftest_user`
--
ALTER TABLE `proftest_user`
  MODIFY `ProftestUserID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- updates
ALTER TABLE `proftest_user` ADD `Status` ENUM('active','reset') NOT NULL DEFAULT 'active' AFTER `Created`;
ALTER TABLE `proftest_user` ADD `ProftestID` INT(10) UNSIGNED NOT NULL AFTER `ProftestUserID`;

