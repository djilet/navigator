ALTER TABLE `users_item` ADD `QuestionModerator` ENUM('Y','N') NOT NULL DEFAULT 'N' AFTER `CommentsStatus`;
