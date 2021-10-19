<?php

class BaseTestQuestion extends LocalObject
{
	private $module;
	private $stmt;

	private $data;

	public function __construct($module = 'basetest')
	{
		parent::LocalObject();
		$this->module = $module;
		$this->stmt = GetStatement();
	}

	public function load($questionID){
		$query = "SELECT QuestionID, Title, Description, DataTable, SortOrder
				  FROM basetest_question
				  WHERE QuestionID = " . intval($questionID);
		$this->LoadFromSQL($query);

		if ($this->IsPropertySet('QuestionID')){
			$this->loadData();
		}
	}

	public function loadData(){
		switch ($this->GetProperty('DataTable')){
			case "WhoWork":
				require_once(dirname(__FILE__) . "/../../data/include/WhoWork.php");
				$who_work = new WhoWork();
				$who_work->load();
				$this->data = $who_work;
				break;

			case "WantWork":
				require_once(dirname(__FILE__) . "/../../data/include/WantWork.php");
				$wantWork = new WantWork();
				$wantWork->load();
				$this->data = $wantWork;
				break;

			case "Industry":
				require_once(dirname(__FILE__) . "/../../data/include/Industry.php");
				$industry = new Industry();
				$industry->load();
				$this->data = $industry;
				break;
		}
	}


	public function getOptionList(TemplateListInterface $data, $answersList){
		$answers = array();
		if (is_array($answersList)){
			foreach ($answersList as $index => $answer) {
				$answers[$answer['ItemID']] = $answer['Position'];
			}
		}

		return $data->getListForTemplate(null, $answers);
	}

	public function getData()//:TemplateList
	{
		return $this->data;
	}

//Static
	public static function getFirstQuestionID(){
		$stmt = GetStatement();
		if ($id = $stmt->FetchField("SELECT QuestionID FROM `basetest_question` ORDER BY SortOrder LIMIT 1")){
			return $id;
		}

		//TODO log
		return false;
	}

	public static function getNotInit($testUserID){
		$stmt = GetStatement();
		$query = "SELECT quest.*
				FROM `basetest_question` AS quest
				LEFT JOIN basetest_result AS result ON quest.QuestionID = result.QuestionID AND result.BaseTestUserID = " . intval($testUserID) . "
				WHERE result.Status IS NULL
				ORDER BY SortOrder";

		if ($result = $stmt->FetchRow($query)){
			return $result;
		}

		return false;
	}
}