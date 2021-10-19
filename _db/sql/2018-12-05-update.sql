-- Change Additional
ALTER TABLE `data_speciality` CHANGE `Additional1` `Additional1` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional2` `Additional2` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional3` `Additional3` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional4` `Additional4` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional5` `Additional5` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional6` `Additional6` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional7` `Additional7` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional8` `Additional8` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional9` `Additional9` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `data_speciality` CHANGE `Additional10` `Additional10` VARCHAR(350) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- OVZ in college_speciality
ALTER TABLE `college_speciality` ADD `OVZ` VARCHAR(255) NULL DEFAULT NULL AFTER `AdmissionBaseID`;