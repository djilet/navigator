<?php

class ProftestAnswer extends LocalObject{
	private $module;
	protected $list;

	public function __construct($module = 'proftest'){
		parent::LocalObject();
		$this->module = $module;
		$this->list = new LocalObjectList();
	}

	public function loadByID($id){
		$this->LoadFromSQL("SELECT * FROM proftest_answer WHERE AnswerID = " . intval($id));
	}

	public function save(LocalObject $request){
		$stmt = GetStatement();

		if (!$this->validate($request)){
			return false;
		}

		if ($request->GetIntProperty("AnswerID") > 0){
			$op = "UPDATE `proftest_answer` SET";
			$where =  " WHERE AnswerID = " . $request->GetIntProperty("AnswerID");
		}
		else{
			$op = "INSERT INTO `proftest_answer` SET ";
			$where = '';
		}

		$query = $op . "
			`TaskID` = " . $request->GetPropertyForSQL("TaskID") . ",
			`Points` = " . $request->GetPropertyForSQL("Points") . ",
			`Title` = " . $request->GetPropertyForSQL("Title") . ",
			`SortOrder` = " . $request->GetPropertyForSQL("SortOrder") .
			$where;

		if (!$stmt->Execute($query)){
			$this->AddError('answer-save-error', $this->module);
			return false;
		}
		return true;
	}

	public function validate(LocalObject $request){
		$this->ClearErrors();
		if(empty($request->GetProperty('Points'))){
			$this->AddError('empty-points', $this->module);
		}
		if(empty($request->GetProperty('Title'))){
			$this->AddError('empty-title', $this->module);
		}
		if (!is_numeric($request->GetProperty('SortOrder'))){
			$this->AddError('sort-order-not-numeric', $this->module);
		}

		if ($this->HasErrors()){
			return false;
		}
		return true;
	}

	public function remove($id){
		$stmt = GetStatement();

		$query = "DELETE FROM proftest_answer WHERE AnswerID = " . intval($id);
		$stmt->Execute($query);
	}

//List
	public function getObjectList(){
		return $this->list;
	}

	public function loadList($taskID){
		$this->list->LoadFromSQL("SELECT * FROM proftest_answer WHERE TaskID = " . intval($taskID) . " ORDER BY SortOrder");
	}
}