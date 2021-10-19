ALTER TABLE `users_item` ADD `CommentsStatus` ENUM('Y','N') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Y' AFTER `ChatLimitDate`;
