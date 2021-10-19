<?php

class BaseTestQuestionList extends LocalObjectList
{
	public function __construct(){
		parent::LocalObjectList();
	}

	public function loadForTestUser($testUserID, $selected = null){
		$query = "SELECT quest.*, result.Status, (CASE quest.QuestionID WHEN " . intval($selected) . " THEN 1 ELSE 0 END) as Selected
				FROM `basetest_question` AS quest
				LEFT JOIN basetest_result AS result ON quest.QuestionID = result.QuestionID AND result.BaseTestUserID = " . intval($testUserID) . "
				ORDER BY SortOrder";
		$this->LoadFromSQL($query);
	}

	public function getItems($selected = array()){
		$result = array();
		foreach ($this->_items as $index => $item) {
			if (!empty($selected)){
				if (in_array($item['QuestionID'], $selected)){
					$item['Selected'] = 1;
				}
			}
			$result[] = $item;
		}

		return $result;
	}

	public function getStatForTestUser(){
		$stat['AvailableCount'] = 0;
		$stat['CompletedCount'] = 0;
		$stat['NotInitCount'] = 0;

		$stat['AllQuestionCount'] = count($this->_items);
		foreach ($this->_items as $key => $item) {
			switch ($item['Status']){
				case BaseTestQuestionResult::STATUS_AVAILABLE:
					$stat['AvailableCount']++;
					break;

				case BaseTestQuestionResult::STATUS_COMPLETE:
					$stat['CompletedCount']++;
					break;

				default:
					$stat['NotInitCount']++;
					break;
			}

			if ($item['Selected'] > 0 && !isset($stat['NextQuestion'])){
				if (isset($this->_items[$key + 1])){
					$stat['NextQuestion'] = $this->_items[$key + 1];
				}
			}
		}

		return $stat;
	}
}