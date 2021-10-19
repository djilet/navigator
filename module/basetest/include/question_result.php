<?php

class BaseTestQuestionResult extends LocalObject
{
	protected $module;
	protected $stmt;
	public $list;

	const STATUS_COMPLETE = 'complete';
	const STATUS_AVAILABLE = 'available';

	public function __construct($module = 'basetest')
	{
		parent::LocalObject();
		$this->module = $module;
		$this->stmt = GetStatement();
	}
//Load
	public function load($questionResultID){
		$query = "SELECT QuestionResultID, BaseTestUserID, QuestionID, Status
				  FROM basetest_result
				  WHERE QuestionResultID = " . intval($questionResultID);
		$this->LoadFromSQL($query);

		if ($this->prepare()){
			return true;
		}
	}

	public function loadActive($testUserID){
		$query = "SELECT QuestionResultID, BaseTestUserID, QuestionID, Status
				  FROM basetest_result
				  WHERE BaseTestUserID = " . intval($testUserID) . "
				  AND Status = 'available'";
		$this->LoadFromSQL($query);

		if ($this->prepare()){
			return true;
		}

		//TODO log
		return false;
	}

	public function prepare(){
		if ($this->IsPropertySet('QuestionResultID')){
			$this->SetProperty('Answers', $this->getAnswers());
			return true;
		}
	}

//Get
	public function getAnswers(){
		$query = "SELECT * FROM basetest_result_answers
				WHERE QuestionResultID = " . $this->GetIntProperty('QuestionResultID');
		if ($result = $this->stmt->FetchList($query)){
			return $result;
		}

		return false;
	}

//Init
	public function init($testUserID, $questionID){
		$this->SetProperty('BaseTestUserID', $testUserID);
		$this->SetProperty('QuestionID', $questionID);
		$this->SetProperty('Status', self::STATUS_AVAILABLE);
		return $this->save();
	}

//CRUD
	public function save(){
		$where = '';

		if ($id = $this->GetIntProperty('QuestionResultID')){
			$query = "UPDATE";
			$where = "\n WHERE QuestionResultID = " . $this->GetPropertyForSQL('QuestionResultID');
		}
		else{
			$query = "INSERT INTO";
		}

		$query .= " basetest_result SET
 				QuestionResultID 		= " . ($id > 0 ? $id : "NULL") . ",
 				BaseTestUserID 		= " . $this->GetPropertyForSQL('BaseTestUserID') . ",
 				QuestionID 			= " . $this->GetPropertyForSQL('QuestionID') . ",
 				Status 				= " . $this->GetPropertyForSQL('Status') .
				$where;

		if ($this->stmt->Execute($query)){
			if ($id < 1){
				$id = $this->stmt->_lastInsertID;
			}
			$this->saveAnswers();
			return $id;
		}
		else{
			//TODO log
			return false;
		}
	}

	public function saveAnswers(){
		$answers = $this->GetProperty('Answers');
		if (!empty($answers)){
			$this->deleteAnswers();
			foreach ($answers as $key => $answer) {
				$query = "INSERT INTO basetest_result_answers SET
					  QuestionResultID = " . $this->GetIntProperty('QuestionResultID') . ",
					  ItemID = " . intval($answer['Id']) . ",
					  Position = " . intval($answer['Position']);
				$this->stmt->Execute($query);
			}
		}
	}

	public function deleteAnswers(){
		$query = "DELETE FROM basetest_result_answers WHERE QuestionResultID = " . $this->GetIntProperty('QuestionResultID');
		if ($this->stmt->Execute($query)){
			return true;
		}

		return false;
	}

	public static function getIdByFields($testUserID, $questionID){
		$stmt = GetStatement();
		$query = "SELECT QuestionResultID FROM basetest_result
				  WHERE BaseTestUserID = " . intval($testUserID) . "
				  AND QuestionID = " . intval($questionID);

		if ($id = $stmt->FetchField($query)){
			return $id;
		}

		return false;
	}

}