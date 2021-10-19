<?php

class Achievement extends BaseObject {
	public $list;
	public $item;
	protected $module;
	protected $stmt;

	public function __construct($module = 'data'){
		parent::BaseObject();
		$this->module = $module;
		$this->stmt = GetStatement();

		$this->list = new LocalObjectList();
		$this->item = new LocalObject();
	}

//List methods
	public function loadList(){
		$query = 'SELECT * FROM `data_achievement` ORDER BY `SortOrder` ASC';
		$this->list->LoadFromSQL($query);
	}


	public function getItems($selected = array()){
		$result = array();
		foreach ($this->list->_items as $item) {
			$item['Selected'] = in_array($item['AchievementID'], $selected) ? 1 : 0;
			$result[] = $item;
		}

		return $result;
	}

	function removeItems($ids){
		if (is_array($ids) && count($ids) > 0) {
			$query = "DELETE FROM `data_achievement` WHERE AchievementID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$this->stmt->Execute($query);

			if ($this->stmt->GetAffectedRows() > 0) {
				$this->AddMessage("achievement-removed", $this->module, array("Count" => $this->stmt->GetAffectedRows()));
			}
		}
	}




//Item methods
	public function loadByID($id){
		$query = "SELECT * FROM data_achievement WHERE AchievementID = " . intval($id);
		$this->item->LoadFromSQL($query);
		return true;
	}

	public function getItem(){
		return $this->item;
	}

	public function save(){
		$this->ClearErrors();
		if(!$this->validate()){
			return false;
		}

		$id = $this->item->GetProperty('AchievementID');
		$title = $this->item->GetProperty('Title');
		$sortOrder = $this->item->GetProperty('SortOrder');
		//Insert
			if ($id > 0) {
				$query = "UPDATE `data_achievement` SET
						Title=" . Connection::GetSQLString($title) . ",
						SortOrder=" . Connection::GetSQLString($sortOrder) . "
						WHERE AchievementID = " . intval( $id );
			}
			else{
				$query = "INSERT INTO `data_achievement` SET
						Title = " . Connection::GetSQLString($title) .",
						SortOrder=" . Connection::GetSQLString($sortOrder);
			}

			if( $this->stmt->Execute($query) ){
				return true;
			}
			else{
				return false;
			}
	}

	public function validate(){
		if (!$this->item->ValidateNotEmpty('Title')){
			$this->AddError("achievement-title-empty", $this->module);
		}
		if (!$this->item->ValidateInt('SortOrder')){
			$this->AddError("sort-order-not-numeric", $this->module);
		}

		if (!$this->HasErrors()){
			return true;
		}
	}
}