ALTER TABLE `proftest_item` ADD `Text` TEXT NULL DEFAULT NULL AFTER `PageID`;
ALTER TABLE `proftest_task` ADD `Prefix` TEXT NULL DEFAULT NULL AFTER `Type`;

UPDATE proftest_answer SET Title = 'очень не нравится' WHERE Title = '-2';
UPDATE proftest_answer SET Title = 'не нравится' WHERE Title = '-1';
UPDATE proftest_answer SET Title = 'все равно' WHERE Title = '0';
UPDATE proftest_answer SET Title = 'нравится' WHERE Title = '1';
UPDATE proftest_answer SET Title = 'очень нравится' WHERE Title = '2';
UPDATE proftest_task SET Prefix = 'Насколько вам нравится';

UPDATE `proftest_item` SET `Text` = '<p>Вам предстоит оценить свои интересы в пределах 20 направлений. Если то или иное занятие вам очень нравится, поставьте ему оценку +2, если просто нравится +1, если безразлично 0, если вы не любите этим заниматься -1, если совсем не нравится -2.</p>'

-- New Field
ALTER TABLE `proftest_category` ADD `Profession` TEXT NULL DEFAULT NULL AFTER `Title`, ADD `Subjects` TEXT NULL DEFAULT NULL AFTER `Profession`;
UPDATE proftest_category SET Subjects = '<ul><li>Предмет 1</li><li>Предмет 2</li><li>Предмет 3</li></ul>';
UPDATE proftest_category SET Profession = '<ul><li>Профессия 1</li><li>Профессия 2</li><li>Профессия 3</li></ul>';
