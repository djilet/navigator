-- 2018-11-01 - closed universities
ALTER TABLE `data_university` ADD `Opened` ENUM('Y','N') NOT NULL DEFAULT 'Y' AFTER `SortOrder`;

-- 2018-11-02 - move schedule to separate tables
ALTER TABLE `data_exhibition_city` DROP `Shedule`;

-- 2018-11-20 - college-synonyms
ALTER TABLE `college_college` ADD `Synonyms` VARCHAR(255) NULL AFTER `Title`;

-- 2018-11-24 - data_speciality index
CREATE UNIQUE INDEX ID_Year_Type ON data_speciality_study(SpecialityID, Year, Type)

-- 2018-11-26 - remove variable professions
ALTER TABLE `data_speciality` CHANGE `VariableProfession` `VariableProfession` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

--2018-11-26 - description for university
ALTER TABLE `data_university` ADD `Description` TEXT NULL AFTER `WhyChoose`;