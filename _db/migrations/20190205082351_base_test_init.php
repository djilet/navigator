<?php


use Phinx\Migration\AbstractMigration;

class BaseTestInit extends AbstractMigration
{

	public function up(){
		$sql = "CREATE TABLE `basetest_question` (
			  `QuestionID` int(10) NOT NULL,
			  `Title` varchar(255) NOT NULL,
			  `Description` text,
			  `DataTable` varchar(30) NOT NULL,
			  `SortOrder` int(10) DEFAULT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			
			CREATE TABLE `basetest_result` (
			  `QuestionResultID` int(10) NOT NULL,
			  `BaseTestUserID` int(10) NOT NULL,
			  `QuestionID` int(10) NOT NULL,
			  `Status` enum('available','complete') DEFAULT 'available'
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			
			CREATE TABLE `basetest_result_answers` (
			  `AnswerID` int(10) NOT NULL,
			  `QuestionResultID` int(10) NOT NULL,
			  `ItemID` int(10) DEFAULT NULL,
			  `Position` int(10) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			
			CREATE TABLE `basetest_user` (
			  `BaseTestUserID` int(10) NOT NULL,
			  `UserID` int(10) DEFAULT NULL,
			  `Created` datetime DEFAULT NULL,
			  `Status` enum('active','reset') DEFAULT 'active',
			  `CompleteDate` datetime DEFAULT NULL,
			  `FeedbackRating` int(11) DEFAULT NULL,
			  `FeedbackMessage` varchar(255) DEFAULT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			
			
			ALTER TABLE `basetest_question`
			  ADD PRIMARY KEY (`QuestionID`);
			
			ALTER TABLE `basetest_result`
			  ADD PRIMARY KEY (`QuestionResultID`);
			
			ALTER TABLE `basetest_result_answers`
			  ADD PRIMARY KEY (`AnswerID`);
			
			ALTER TABLE `basetest_user`
			  ADD PRIMARY KEY (`BaseTestUserID`);
			
			
			ALTER TABLE `basetest_question`
			  MODIFY `QuestionID` int(10) NOT NULL AUTO_INCREMENT;
			ALTER TABLE `basetest_result`
			  MODIFY `QuestionResultID` int(10) NOT NULL AUTO_INCREMENT;
			ALTER TABLE `basetest_result_answers`
			  MODIFY `AnswerID` int(10) NOT NULL AUTO_INCREMENT;
			ALTER TABLE `basetest_user`
			  MODIFY `BaseTestUserID` int(10) NOT NULL AUTO_INCREMENT;
			  
		  	ALTER TABLE basetest_user ADD FeedbackRating int DEFAULT null  NULL;
			ALTER TABLE basetest_user ADD FeedbackMessage varchar(255) DEFAULT null  NULL;
			ALTER TABLE `basetest_user` ADD `CompleteDate` DATETIME NULL DEFAULT NULL AFTER `Status`;";

		$this->execute($sql);
	}

	public function down()
	{
		$sql = 'DROP TABLE basetest_question, basetest_result, basetest_result_answers, basetest_user';
		$this->execute($sql);
	}
}
