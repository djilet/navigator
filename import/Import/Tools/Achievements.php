<?php

namespace Import\Tools;


class Achievements{

	private $stmt;
	private $achievements;

	public function __construct(\Statement $stmt){
		$this->stmt = $stmt;
		$this->achievements = $this->stmt->FetchIndexedAssocList(
			'SELECT `AchievementID`,`Title` FROM `data_achievement`',
			'Title'
		);
	}

	public function getId($title, $saveNew = true){
		if (!isset($this->achievements[$title])) {
			if ($saveNew == true){
				$this->insert($title);
			}
			else{
				//TODO Error list
			}
		}

		return $this->achievements[$title]['AchievementID'];
	}

	private function insert($title){
		//$sortOrder = $this->stmt->FetchField('SELECT MAX(`SortOrder`)+1 FROM `data_achievement`');
		$sortOrder = 0;
		if (!$sortOrder) {
			$sortOrder = 0;
		}

		$query = "INSERT INTO `data_achievement` SET
                        `Title` = " . \Connection::GetSQLString($title) . ",
                    `SortOrder` = " . $sortOrder;
		if ($this->stmt->Execute($query)) {
			$this->achievements[$title] = [
				'Title'    => $title,
				'AchievementID' => $this->stmt->GetLastInsertID(),
			];
		}
	}
}