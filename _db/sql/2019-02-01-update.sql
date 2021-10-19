ALTER TABLE basetest_user ADD FeedbackRating int DEFAULT null  NULL;
ALTER TABLE basetest_user ADD FeedbackMessage varchar(255) DEFAULT null  NULL;
ALTER TABLE `basetest_user` ADD `CompleteDate` DATETIME NULL DEFAULT NULL AFTER `Status`;