DROP TABLE IF EXISTS`proftest_answer`, `proftest_answer2user`, `proftest_category`, `proftest_item`, `proftest_task`, `proftest_task2category`, `proftest_task2user`, `proftest_user`;

CREATE TABLE `proftest_answer` (
  `AnswerID` int(10) UNSIGNED NOT NULL,
  `TaskID` int(10) UNSIGNED NOT NULL,
  `Points` int(10) NOT NULL,
  `SortOrder` int(5) UNSIGNED DEFAULT NULL,
  `Title` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `proftest_answer`
--

INSERT INTO `proftest_answer` (`AnswerID`, `TaskID`, `Points`, `SortOrder`, `Title`) VALUES
(1, 1, -2, NULL, '-2'),
(2, 1, -1, NULL, '-1'),
(3, 1, 0, NULL, '0'),
(4, 1, 1, NULL, '1'),
(5, 1, 2, NULL, '2'),
(6, 2, -2, NULL, '-2'),
(7, 2, -1, NULL, '-1'),
(8, 2, 0, NULL, '0'),
(9, 2, 1, NULL, '1'),
(10, 2, 2, NULL, '2'),
(11, 3, -2, NULL, '-2'),
(12, 3, -1, NULL, '-1'),
(13, 3, 0, NULL, '0'),
(14, 3, 1, NULL, '1'),
(15, 3, 2, NULL, '2'),
(16, 4, -2, NULL, '-2'),
(17, 4, -1, NULL, '-1'),
(18, 4, 0, NULL, '0'),
(19, 4, 1, NULL, '1'),
(20, 4, 2, NULL, '2'),
(21, 5, -2, NULL, '-2'),
(22, 5, -1, NULL, '-1'),
(23, 5, 0, NULL, '0'),
(24, 5, 1, NULL, '1'),
(25, 5, 2, NULL, '2'),
(26, 6, -2, NULL, '-2'),
(27, 6, -1, NULL, '-1'),
(28, 6, 0, NULL, '0'),
(29, 6, 1, NULL, '1'),
(30, 6, 2, NULL, '2'),
(31, 7, -2, NULL, '-2'),
(32, 7, -1, NULL, '-1'),
(33, 7, 0, NULL, '0'),
(34, 7, 1, NULL, '1'),
(35, 7, 2, NULL, '2'),
(36, 8, -2, NULL, '-2'),
(37, 8, -1, NULL, '-1'),
(38, 8, 0, NULL, '0'),
(39, 8, 1, NULL, '1'),
(40, 8, 2, NULL, '2'),
(41, 9, -2, NULL, '-2'),
(42, 9, -1, NULL, '-1'),
(43, 9, 0, NULL, '0'),
(44, 9, 1, NULL, '1'),
(45, 9, 2, NULL, '2'),
(46, 10, -2, NULL, '-2'),
(47, 10, -1, NULL, '-1'),
(48, 10, 0, NULL, '0'),
(49, 10, 1, NULL, '1'),
(50, 10, 2, NULL, '2'),
(51, 11, -2, NULL, '-2'),
(52, 11, -1, NULL, '-1'),
(53, 11, 0, NULL, '0'),
(54, 11, 1, NULL, '1'),
(55, 11, 2, NULL, '2'),
(56, 12, -2, NULL, '-2'),
(57, 12, -1, NULL, '-1'),
(58, 12, 0, NULL, '0'),
(59, 12, 1, NULL, '1'),
(60, 12, 2, NULL, '2'),
(61, 13, -2, NULL, '-2'),
(62, 13, -1, NULL, '-1'),
(63, 13, 0, NULL, '0'),
(64, 13, 1, NULL, '1'),
(65, 13, 2, NULL, '2'),
(66, 14, -2, NULL, '-2'),
(67, 14, -1, NULL, '-1'),
(68, 14, 0, NULL, '0'),
(69, 14, 1, NULL, '1'),
(70, 14, 2, NULL, '2'),
(71, 15, -2, NULL, '-2'),
(72, 15, -1, NULL, '-1'),
(73, 15, 0, NULL, '0'),
(74, 15, 1, NULL, '1'),
(75, 15, 2, NULL, '2'),
(76, 16, -2, NULL, '-2'),
(77, 16, -1, NULL, '-1'),
(78, 16, 0, NULL, '0'),
(79, 16, 1, NULL, '1'),
(80, 16, 2, NULL, '2'),
(81, 17, -2, NULL, '-2'),
(82, 17, -1, NULL, '-1'),
(83, 17, 0, NULL, '0'),
(84, 17, 1, NULL, '1'),
(85, 17, 2, NULL, '2'),
(86, 18, -2, NULL, '-2'),
(87, 18, -1, NULL, '-1'),
(88, 18, 0, NULL, '0'),
(89, 18, 1, NULL, '1'),
(90, 18, 2, NULL, '2'),
(91, 19, -2, NULL, '-2'),
(92, 19, -1, NULL, '-1'),
(93, 19, 0, NULL, '0'),
(94, 19, 1, NULL, '1'),
(95, 19, 2, NULL, '2'),
(96, 20, -2, NULL, '-2'),
(97, 20, -1, NULL, '-1'),
(98, 20, 0, NULL, '0'),
(99, 20, 1, NULL, '1'),
(100, 20, 2, NULL, '2'),
(101, 21, -2, NULL, '-2'),
(102, 21, -1, NULL, '-1'),
(103, 21, 0, NULL, '0'),
(104, 21, 1, NULL, '1'),
(105, 21, 2, NULL, '2'),
(106, 22, -2, NULL, '-2'),
(107, 22, -1, NULL, '-1'),
(108, 22, 0, NULL, '0'),
(109, 22, 1, NULL, '1'),
(110, 22, 2, NULL, '2'),
(111, 23, -2, NULL, '-2'),
(112, 23, -1, NULL, '-1'),
(113, 23, 0, NULL, '0'),
(114, 23, 1, NULL, '1'),
(115, 23, 2, NULL, '2'),
(116, 24, -2, NULL, '-2'),
(117, 24, -1, NULL, '-1'),
(118, 24, 0, NULL, '0'),
(119, 24, 1, NULL, '1'),
(120, 24, 2, NULL, '2'),
(121, 25, -2, NULL, '-2'),
(122, 25, -1, NULL, '-1'),
(123, 25, 0, NULL, '0'),
(124, 25, 1, NULL, '1'),
(125, 25, 2, NULL, '2'),
(126, 26, -2, NULL, '-2'),
(127, 26, -1, NULL, '-1'),
(128, 26, 0, NULL, '0'),
(129, 26, 1, NULL, '1'),
(130, 26, 2, NULL, '2'),
(131, 27, -2, NULL, '-2'),
(132, 27, -1, NULL, '-1'),
(133, 27, 0, NULL, '0'),
(134, 27, 1, NULL, '1'),
(135, 27, 2, NULL, '2'),
(136, 28, -2, NULL, '-2'),
(137, 28, -1, NULL, '-1'),
(138, 28, 0, NULL, '0'),
(139, 28, 1, NULL, '1'),
(140, 28, 2, NULL, '2'),
(141, 29, -2, NULL, '-2'),
(142, 29, -1, NULL, '-1'),
(143, 29, 0, NULL, '0'),
(144, 29, 1, NULL, '1'),
(145, 29, 2, NULL, '2'),
(146, 30, -2, NULL, '-2'),
(147, 30, -1, NULL, '-1'),
(148, 30, 0, NULL, '0'),
(149, 30, 1, NULL, '1'),
(150, 30, 2, NULL, '2'),
(151, 31, -2, NULL, '-2'),
(152, 31, -1, NULL, '-1'),
(153, 31, 0, NULL, '0'),
(154, 31, 1, NULL, '1'),
(155, 31, 2, NULL, '2'),
(156, 32, -2, NULL, '-2'),
(157, 32, -1, NULL, '-1'),
(158, 32, 0, NULL, '0'),
(159, 32, 1, NULL, '1'),
(160, 32, 2, NULL, '2'),
(161, 33, -2, NULL, '-2'),
(162, 33, -1, NULL, '-1'),
(163, 33, 0, NULL, '0'),
(164, 33, 1, NULL, '1'),
(165, 33, 2, NULL, '2'),
(166, 34, -2, NULL, '-2'),
(167, 34, -1, NULL, '-1'),
(168, 34, 0, NULL, '0'),
(169, 34, 1, NULL, '1'),
(170, 34, 2, NULL, '2'),
(171, 35, -2, NULL, '-2'),
(172, 35, -1, NULL, '-1'),
(173, 35, 0, NULL, '0'),
(174, 35, 1, NULL, '1'),
(175, 35, 2, NULL, '2'),
(176, 36, -2, NULL, '-2'),
(177, 36, -1, NULL, '-1'),
(178, 36, 0, NULL, '0'),
(179, 36, 1, NULL, '1'),
(180, 36, 2, NULL, '2'),
(181, 37, -2, NULL, '-2'),
(182, 37, -1, NULL, '-1'),
(183, 37, 0, NULL, '0'),
(184, 37, 1, NULL, '1'),
(185, 37, 2, NULL, '2'),
(186, 38, -2, NULL, '-2'),
(187, 38, -1, NULL, '-1'),
(188, 38, 0, NULL, '0'),
(189, 38, 1, NULL, '1'),
(190, 38, 2, NULL, '2'),
(191, 39, -2, NULL, '-2'),
(192, 39, -1, NULL, '-1'),
(193, 39, 0, NULL, '0'),
(194, 39, 1, NULL, '1'),
(195, 39, 2, NULL, '2'),
(196, 40, -2, NULL, '-2'),
(197, 40, -1, NULL, '-1'),
(198, 40, 0, NULL, '0'),
(199, 40, 1, NULL, '1'),
(200, 40, 2, NULL, '2'),
(201, 41, -2, NULL, '-2'),
(202, 41, -1, NULL, '-1'),
(203, 41, 0, NULL, '0'),
(204, 41, 1, NULL, '1'),
(205, 41, 2, NULL, '2'),
(206, 42, -2, NULL, '-2'),
(207, 42, -1, NULL, '-1'),
(208, 42, 0, NULL, '0'),
(209, 42, 1, NULL, '1'),
(210, 42, 2, NULL, '2'),
(211, 43, -2, NULL, '-2'),
(212, 43, -1, NULL, '-1'),
(213, 43, 0, NULL, '0'),
(214, 43, 1, NULL, '1'),
(215, 43, 2, NULL, '2'),
(216, 44, -2, NULL, '-2'),
(217, 44, -1, NULL, '-1'),
(218, 44, 0, NULL, '0'),
(219, 44, 1, NULL, '1'),
(220, 44, 2, NULL, '2'),
(221, 45, -2, NULL, '-2'),
(222, 45, -1, NULL, '-1'),
(223, 45, 0, NULL, '0'),
(224, 45, 1, NULL, '1'),
(225, 45, 2, NULL, '2'),
(226, 46, -2, NULL, '-2'),
(227, 46, -1, NULL, '-1'),
(228, 46, 0, NULL, '0'),
(229, 46, 1, NULL, '1'),
(230, 46, 2, NULL, '2'),
(231, 47, -2, NULL, '-2'),
(232, 47, -1, NULL, '-1'),
(233, 47, 0, NULL, '0'),
(234, 47, 1, NULL, '1'),
(235, 47, 2, NULL, '2'),
(236, 48, -2, NULL, '-2'),
(237, 48, -1, NULL, '-1'),
(238, 48, 0, NULL, '0'),
(239, 48, 1, NULL, '1'),
(240, 48, 2, NULL, '2'),
(241, 49, -2, NULL, '-2'),
(242, 49, -1, NULL, '-1'),
(243, 49, 0, NULL, '0'),
(244, 49, 1, NULL, '1'),
(245, 49, 2, NULL, '2'),
(246, 50, -2, NULL, '-2'),
(247, 50, -1, NULL, '-1'),
(248, 50, 0, NULL, '0'),
(249, 50, 1, NULL, '1'),
(250, 50, 2, NULL, '2'),
(251, 51, -2, NULL, '-2'),
(252, 51, -1, NULL, '-1'),
(253, 51, 0, NULL, '0'),
(254, 51, 1, NULL, '1'),
(255, 51, 2, NULL, '2'),
(256, 52, -2, NULL, '-2'),
(257, 52, -1, NULL, '-1'),
(258, 52, 0, NULL, '0'),
(259, 52, 1, NULL, '1'),
(260, 52, 2, NULL, '2'),
(261, 53, -2, NULL, '-2'),
(262, 53, -1, NULL, '-1'),
(263, 53, 0, NULL, '0'),
(264, 53, 1, NULL, '1'),
(265, 53, 2, NULL, '2'),
(266, 54, -2, NULL, '-2'),
(267, 54, -1, NULL, '-1'),
(268, 54, 0, NULL, '0'),
(269, 54, 1, NULL, '1'),
(270, 54, 2, NULL, '2'),
(271, 55, -2, NULL, '-2'),
(272, 55, -1, NULL, '-1'),
(273, 55, 0, NULL, '0'),
(274, 55, 1, NULL, '1'),
(275, 55, 2, NULL, '2');

-- --------------------------------------------------------

--
-- Table structure for table `proftest_answer2user`
--

CREATE TABLE `proftest_answer2user` (
  `ProftestUserID` int(10) UNSIGNED NOT NULL,
  `TaskID` int(10) UNSIGNED NOT NULL,
  `AnswerID` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proftest_category`
--

CREATE TABLE `proftest_category` (
  `ProftestID` int(10) NOT NULL,
  `CategoryID` int(10) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `SortOrder` int(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `proftest_category`
--

INSERT INTO `proftest_category` (`ProftestID`, `CategoryID`, `Title`, `SortOrder`) VALUES
(1, 1, 'Биология, медицина, психология', NULL),
(1, 2, 'Точные науки: физика, математика, химия', NULL),
(1, 3, 'Механика, техника, электроника, транспорт', NULL),
(1, 4, 'Информационные технологии, программирование', NULL),
(1, 5, 'Строительство, архитектура, дизайн, искусство', NULL),
(1, 6, 'Литература, журналистика, связи с общественностью', NULL),
(1, 7, 'Социология, педагогика, политика, менеджмент', NULL),
(1, 8, 'Право, юриспруденция, история, военные специальности', NULL),
(1, 9, 'Сфера обслуживания, туризм', NULL),
(1, 10, 'Экономика, бизнес', NULL),
(1, 11, 'Иностранные языки, лингвистика', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `proftest_item`
--

CREATE TABLE `proftest_item` (
  `ProftestID` int(10) UNSIGNED NOT NULL,
  `PageID` int(10) UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `proftest_item`
--

INSERT INTO `proftest_item` (`ProftestID`, `PageID`) VALUES
(1, 134);

-- --------------------------------------------------------

--
-- Table structure for table `proftest_task`
--

CREATE TABLE `proftest_task` (
  `TaskID` int(10) UNSIGNED NOT NULL,
  `ProftestID` int(10) UNSIGNED NOT NULL,
  `SortOrder` int(5) UNSIGNED NOT NULL,
  `Type` enum('radio','checkbox') NOT NULL,
  `Text` text NOT NULL,
  `AnswerCount` int(2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `proftest_task`
--

INSERT INTO `proftest_task` (`TaskID`, `ProftestID`, `SortOrder`, `Type`, `Text`, `AnswerCount`) VALUES
(1, 1, 0, 'checkbox', '1. Узнавать об экзотических растениях и животных.', NULL),
(2, 1, 0, 'checkbox', '2. Изучать карты стран, соседей различных государств', NULL),
(3, 1, 0, 'checkbox', '3. Изучать известные компании и истории их успеха.', NULL),
(4, 1, 0, 'checkbox', '4. Узнавать о причинах возникновения болезней и методах их лечения.', NULL),
(5, 1, 0, 'checkbox', '5. Заниматься домашними делами, готовить, заниматься рукоделием.', NULL),
(6, 1, 0, 'checkbox', '6. Читать научно-популярную литературу о физических открытиях.', NULL),
(7, 1, 0, 'checkbox', '7. Изучать техническую литературу об автомобилях, технике.', NULL),
(8, 1, 0, 'checkbox', '8. Изучать свойства различных материалов.', NULL),
(9, 1, 0, 'checkbox', '9. Разрабатывать компьютерные программы, программировать сайты.', NULL),
(10, 1, 0, 'checkbox', '10. Наблюдать за поведением людей, анализировать их поступки.', NULL),
(11, 1, 0, 'checkbox', '11. Помогать родителям при благоустройстве дачи', NULL),
(12, 1, 0, 'checkbox', '12. Интересоваться оружием, разработками в этой области.', NULL),
(13, 1, 0, 'checkbox', '13. Изучать историю своей страны и зарубежную историю.', NULL),
(14, 1, 0, 'checkbox', '14. Придумывать свои стихи или рассказы.', NULL),
(15, 1, 0, 'checkbox', '15. Читать журналы о политике, экономике.', NULL),
(16, 1, 0, 'checkbox', '16. Изучать закономерности развития общества, современные тенденции в обществе.', NULL),
(17, 1, 0, 'checkbox', '17. Заниматься с младшими братьями/сестрами. ', NULL),
(18, 1, 0, 'checkbox', '18. Смотреть фильмы или сериалы про детективов.', NULL),
(19, 1, 0, 'checkbox', '19. Советовать при приобретении различных товаров.', NULL),
(20, 1, 0, 'checkbox', '20. Заниматься математикой, работать с числами', NULL),
(21, 1, 0, 'checkbox', '21. Изучать банковскую систему, суть кредитов и займов.', NULL),
(22, 1, 0, 'checkbox', '22. Изучать иностранные языки.', NULL),
(23, 1, 0, 'checkbox', '23. Знакомиться с жизнью великих режиссеров и актеров.', NULL),
(24, 1, 0, 'checkbox', '24. Посещать исторические музеи.', NULL),
(25, 1, 0, 'checkbox', '25. Изучать строение человека, работу внутренних органов.', NULL),
(26, 1, 0, 'checkbox', '26. Изучать физические явления и законы природы.', NULL),
(27, 1, 0, 'checkbox', '27. Знакомиться с новейшими технологиями, узнавать о принципах их работы (очки дополненной реальности и др.)', NULL),
(28, 1, 0, 'checkbox', '28. Разбираться в устройстве различных приборов: фотоаппарата, телефона и др.', NULL),
(29, 1, 0, 'checkbox', '29. Изучать психологию и поведение людей.', NULL),
(30, 1, 0, 'checkbox', '30. Рисовать здания, сложные геометрические фигуры.', NULL),
(31, 1, 0, 'checkbox', '31. Участвовать в военных соревнованиях, играть в страйкболл, пейнтболл и др.', NULL),
(32, 1, 0, 'checkbox', '32. Следить за новостями в мире, обсуждать проблемы общественной жизни.', NULL),
(33, 1, 0, 'checkbox', '33. Помогать одноклассникам с домашним заданием.', NULL),
(34, 1, 0, 'checkbox', '34. Изучать ценообразование, формирование различных стоимостей, различие в заработной плате.', NULL),
(35, 1, 0, 'checkbox', '35. Смотреть телепередачи о путешествиях.', NULL),
(36, 1, 0, 'checkbox', '36. Выступать перед большой аудиторией', NULL),
(37, 1, 0, 'checkbox', '37. Читать книги по психологии.', NULL),
(38, 1, 0, 'checkbox', '38. Посещать художественную школу.', NULL),
(39, 1, 0, 'checkbox', '39. Смотреть исторические фильмы.', NULL),
(40, 1, 0, 'checkbox', '40. Изучать литературу и русский язык.', NULL),
(41, 1, 0, 'checkbox', '41. Общаться с иностранцами.', NULL),
(42, 1, 0, 'checkbox', '42. Проводить опросы, брать интервью.', NULL),
(43, 1, 0, 'checkbox', '43. Придумывать бизнес-планы открытия своего дела. ', NULL),
(44, 1, 0, 'checkbox', '44. Наблюдать за работой поваров, смотреть телепередачи о конкурсах в ресторанном бизнесе.', NULL),
(45, 1, 0, 'checkbox', '45. Вести расчеты доходов, расходов.', NULL),
(46, 1, 0, 'checkbox', '46. Смотреть фильмы или сериалы на иностранном языке.', NULL),
(47, 1, 0, 'checkbox', '47. Заботиться о красивом виде помещения, в котором живешь/учишься.', NULL),
(48, 1, 0, 'checkbox', '48. Смотреть передачи о строительстве и дизайне интерьера', NULL),
(49, 1, 0, 'checkbox', '49. Писать сочинения.', NULL),
(50, 1, 0, 'checkbox', '50. Взаимодействовать с людьми: убеждать, организовывать, руководить.', NULL),
(51, 1, 0, 'checkbox', '51. Продумывать возможные варианты упрощения и упорядочивания различных процессов ', NULL),
(52, 1, 0, 'checkbox', '52. Проводить химические опыты на уроках', NULL),
(53, 1, 0, 'checkbox', '53. Изучать культурные особенности языка', NULL),
(54, 1, 0, 'checkbox', '54. Продумывать идеи мобильных приложений и игр', NULL),
(55, 1, 0, 'checkbox', '55. Изучать языки программирования', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `proftest_task2category`
--

CREATE TABLE `proftest_task2category` (
  `TaskID` int(10) NOT NULL,
  `CategoryID` int(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `proftest_task2category`
--

INSERT INTO `proftest_task2category` (`TaskID`, `CategoryID`) VALUES
(1, 1),
(4, 1),
(25, 1),
(29, 1),
(37, 1),
(6, 2),
(20, 2),
(26, 2),
(52, 2),
(7, 3),
(8, 3),
(27, 3),
(28, 3),
(9, 4),
(51, 4),
(54, 4),
(55, 4),
(11, 5),
(23, 5),
(30, 5),
(38, 5),
(47, 5),
(48, 5),
(14, 6),
(40, 6),
(42, 6),
(49, 6),
(10, 7),
(16, 7),
(17, 7),
(32, 7),
(33, 7),
(36, 7),
(50, 7),
(12, 8),
(13, 8),
(18, 8),
(24, 8),
(31, 8),
(39, 8),
(2, 9),
(5, 9),
(19, 9),
(35, 9),
(44, 9),
(3, 10),
(15, 10),
(21, 10),
(34, 10),
(43, 10),
(45, 10),
(22, 11),
(31, 11),
(46, 11),
(53, 11);

-- --------------------------------------------------------

--
-- Table structure for table `proftest_task2user`
--

CREATE TABLE `proftest_task2user` (
  `TaskSolutionID` int(10) UNSIGNED NOT NULL,
  `ProftestUserID` int(10) UNSIGNED NOT NULL,
  `TaskID` int(10) UNSIGNED NOT NULL,
  `Status` enum('available','complete') NOT NULL DEFAULT 'available',
  `Completed` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proftest_user`
--

CREATE TABLE `proftest_user` (
  `ProftestUserID` int(10) UNSIGNED NOT NULL,
  `ProftestID` int(10) UNSIGNED NOT NULL,
  `UserID` int(10) UNSIGNED DEFAULT NULL,
  `MarathonUserID` int(10) UNSIGNED DEFAULT NULL,
  `Created` datetime NOT NULL,
  `Status` enum('active','reset') NOT NULL DEFAULT 'active',
  `LinkID` varchar(32) DEFAULT NULL,
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
-- Indexes for table `proftest_category`
--
ALTER TABLE `proftest_category`
  ADD PRIMARY KEY (`CategoryID`);

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
  ADD PRIMARY KEY (`ProftestUserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `proftest_answer`
--
ALTER TABLE `proftest_answer`
  MODIFY `AnswerID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;
--
-- AUTO_INCREMENT for table `proftest_category`
--
ALTER TABLE `proftest_category`
  MODIFY `CategoryID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `proftest_item`
--
ALTER TABLE `proftest_item`
  MODIFY `ProftestID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `proftest_task`
--
ALTER TABLE `proftest_task`
  MODIFY `TaskID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;
--
-- AUTO_INCREMENT for table `proftest_task2user`
--
ALTER TABLE `proftest_task2user`
  MODIFY `TaskSolutionID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;
--
-- AUTO_INCREMENT for table `proftest_user`
--
ALTER TABLE `proftest_user`
  MODIFY `ProftestUserID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
