SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `navigator`
--

-- --------------------------------------------------------

--
-- Table structure for table `basetest_question`
--

DROP TABLE IF EXISTS `basetest_question`;
CREATE TABLE `basetest_question` (
  `QuestionID` int(10) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text,
  `DataTable` varchar(30) NOT NULL,
  `SortOrder` int(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `basetest_question`
--

INSERT INTO `basetest_question` (`QuestionID`, `Title`, `Description`, `DataTable`, `SortOrder`) VALUES
  (1, 'С кем хочу работать', 'Описание "Кем хочу работать"', 'WhoWork', 2),
  (2, 'С чем хочу работать', 'Описание "С чем хочу работать"', 'WantWork', 3),
  (3, 'Где хочу работать', 'Описание "С чем хочу работать"', 'Industry', 1);

-- --------------------------------------------------------

--
-- Table structure for table `basetest_result`
--

DROP TABLE IF EXISTS `basetest_result`;
CREATE TABLE `basetest_result` (
  `QuestionResultID` int(10) NOT NULL,
  `BaseTestUserID` int(10) NOT NULL,
  `QuestionID` int(10) NOT NULL,
  `Status` enum('available','complete') DEFAULT 'available'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `basetest_result_answers`
--

DROP TABLE IF EXISTS `basetest_result_answers`;
CREATE TABLE `basetest_result_answers` (
  `AnswerID` int(10) NOT NULL COMMENT 'After remove',
  `QuestionResultID` int(10) NOT NULL,
  `ItemID` int(10) DEFAULT NULL,
  `Position` int(10) NOT NULL COMMENT 'Approve later'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `basetest_user`
--

DROP TABLE IF EXISTS `basetest_user`;
CREATE TABLE `basetest_user` (
  `BaseTestUserID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Created` datetime DEFAULT NULL,
  `Status` enum('active','reset') DEFAULT 'active'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `basetest_question`
--
ALTER TABLE `basetest_question`
  ADD PRIMARY KEY (`QuestionID`);

--
-- Indexes for table `basetest_result`
--
ALTER TABLE `basetest_result`
  ADD PRIMARY KEY (`QuestionResultID`);

--
-- Indexes for table `basetest_result_answers`
--
ALTER TABLE `basetest_result_answers`
  ADD PRIMARY KEY (`AnswerID`);

--
-- Indexes for table `basetest_user`
--
ALTER TABLE `basetest_user`
  ADD PRIMARY KEY (`BaseTestUserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `basetest_question`
--
ALTER TABLE `basetest_question`
  MODIFY `QuestionID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `basetest_result`
--
ALTER TABLE `basetest_result`
  MODIFY `QuestionResultID` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `basetest_result_answers`
--
ALTER TABLE `basetest_result_answers`
  MODIFY `AnswerID` int(10) NOT NULL AUTO_INCREMENT COMMENT 'After remove';
--
-- AUTO_INCREMENT for table `basetest_user`
--
ALTER TABLE `basetest_user`
  MODIFY `BaseTestUserID` int(10) NOT NULL AUTO_INCREMENT;


--
-- Description
--

-- Industry
UPDATE `data_profession_industry` SET `Description` = 'Творческая область для людей, которым необходимо выражать свои мысли и чувства другим. А еще это уютное место для тех, кто хочет изучать законы театра, ТВ, кино, живописи, дизайна, музыки и танца.' WHERE `data_profession_industry`.`IndustryTitle` = 'Культура и искусство';
UPDATE `data_profession_industry` SET `Description` = 'Быть специалистов гуманитарной области значит изучать человечество, историю народов, много общаться словами и текстами, делать добрые дела для всех и каждого. Хорошая область деятельности для тех, кто любит быть среди людей.' WHERE `data_profession_industry`.`IndustryTitle` = 'Гуманитарные области';
UPDATE `data_profession_industry` SET `Description` = 'Желание помогать людям и животным отличает всех медицинских сотрудников. Медицина смотрит людей и животных как на представителей природы, как на биологические организмы. Это работа со здоровыми и больными, с живыми и мертвыми организмами. Надо быть к этому готовыми.' WHERE `data_profession_industry`.`IndustryTitle` = 'Здравоохранение';
UPDATE `data_profession_industry` SET `Description` = 'IT-сфера открывает супервозможности всем любителям алгоритмов и головолок. Это та область, где необходима логика, желание структурировать данные и навести порядок в папках и цифрах.' WHERE `data_profession_industry`.`IndustryTitle` = 'Информационные технологии';
UPDATE `data_profession_industry` SET `Description` = 'Наука - область, где необходима страсть к исследованию и фактам. Специалисты этих областей постоянно что-то измеряют и вычисляют. Изобретать что-то новое, делать великие открытия, изучать неизведенное (например, космос) - вот чем с удовольствием занимаются работники данной отрасли.' WHERE `data_profession_industry`.`IndustryTitle` = 'Наука и техника';
UPDATE `data_profession_industry` SET `Description` = 'Закон - язык, на котором говорит вся юриспруденция и дипломатия. Юриспруденция - подходящий путь, где правда и справедливость стоит на первом месте. А также хороший вариант для любителей точный формулировок.' WHERE `data_profession_industry`.`IndustryTitle` = 'Право и дипломатия';
UPDATE `data_profession_industry` SET `Description` = 'Основная задача любого бизнеса - думать, как получить и не потерять деньги. Это отличная область для тех, кому нравится финансовая сфера или тех, кто хочет привлекать в компанию больше людей, денег, внимания общественности.' WHERE `data_profession_industry`.`IndustryTitle` = 'Бизнес и администрирование';
UPDATE `data_profession_industry` SET `Description` = 'Жажда делиться своими знаниями - ключевая характеристика специалистов в образовании. Также данное направление отлично подходит людям, которые обожают учиться сами. ' WHERE `data_profession_industry`.`IndustryTitle` = 'Воспитание и образование';

-- WhoWork
UPDATE `data_profession_who_work` SET `Description` = 'Тексты, книги, цифры, формулы, таблицы данных ' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Информация';
UPDATE `data_profession_who_work` SET `Description` = 'Большой интерес к общению, к уникальности самого человека' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Люди';
UPDATE `data_profession_who_work` SET `Description` = 'Вложения, планирование расходов, выгодная покупка и продажа, экономика' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Финансы';
UPDATE `data_profession_who_work` SET `Description` = 'Машины, станки, оборудование, роботы, механизмы, чертежи и схемы' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Техника';
UPDATE `data_profession_who_work` SET `Description` = 'Танцы, музыка, рисунок, пение, музыкальные инструменты, музеи, кино, ТВ' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Искусство';
UPDATE `data_profession_who_work` SET `Description` = 'От самых примитивных бактерий и водорослей, до совершенных кораллов, мохнатых гусениц и умных собачек' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Растения и животные';
UPDATE `data_profession_who_work` SET `Description` = 'Вся неживая природа: полезные ископаемые, водоёмы, ледники и пр.' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Природные ресурсы';
UPDATE `data_profession_who_work` SET `Description` = 'Еда на любой стадии приготовления: от заготовки шоколада для партии конфет до приготовлении изысканных блюд для ресторана' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Продукты';
UPDATE `data_profession_who_work` SET `Description` = 'Одежда и обувь, предметы быта, ручные поделки, гаджеты и т.п. ' WHERE `data_profession_who_work`.`WhoWorkTitle` = 'Мир вещей';

-- WantWork
UPDATE `data_profession_want_work` SET `Description` = 'Изучать, анализировать, искать суть, сравнивать' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Исследовать';
UPDATE `data_profession_want_work` SET `Description` = 'Организовывать процесс, распоряжаться, планировать' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Управлять';
UPDATE `data_profession_want_work` SET `Description` = 'Консультировать, оказывать сервис, исполнять чьи-то заказы' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Обслуживать';
UPDATE `data_profession_want_work` SET `Description` = 'Воспитывать, объяснять, делать кого-то лучше' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Обучать';
UPDATE `data_profession_want_work` SET `Description` = 'Лечить, улучшать, помогать' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Заботиться';
UPDATE `data_profession_want_work` SET `Description` = 'Создавать новое, придумывать, креативить, делать что-то уникальное' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Творить';
UPDATE `data_profession_want_work` SET `Description` = 'Воспроизводить, повторять по инструкции, делать массовое или масштабное' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Производить';
UPDATE `data_profession_want_work` SET `Description` = 'Комбинировать, строить, составлять из частей целое' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Конструировать';
UPDATE `data_profession_want_work` SET `Description` = 'Беречь, ограждать, отстаивать, охранять' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Защищать';
UPDATE `data_profession_want_work` SET `Description` = 'Фиксировать, проверять, оценивать' WHERE `data_profession_want_work`.`WantWorkTitle` = 'Контролировать';
