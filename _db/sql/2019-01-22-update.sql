ALTER TABLE `user_tracking` CHANGE `ID` `ID` INT(15) NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_profession_industry` ADD `Description` TEXT NULL DEFAULT NULL AFTER `IndustryTitle`;
ALTER TABLE `data_profession_industry` ADD `ItemImage` VARCHAR(255) NULL DEFAULT NULL AFTER `Description`;

ALTER TABLE `data_profession_who_work` ADD `Description` TEXT NULL DEFAULT NULL AFTER `WhoWorkTitle`;
ALTER TABLE `data_profession_who_work` ADD `ItemImage` VARCHAR(255) NULL DEFAULT NULL AFTER `Description`;

ALTER TABLE `data_profession_want_work` ADD `Description` TEXT NULL DEFAULT NULL AFTER `Alias`;
ALTER TABLE `data_profession_want_work` ADD `ItemImage` VARCHAR(255) NULL DEFAULT NULL AFTER `Description`;
