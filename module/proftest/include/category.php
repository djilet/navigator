<?php

class ProftestCategory extends LocalObject{
	private $module;
	protected $list;

	public function __construct($module = 'proftest'){
		parent::LocalObject();
		$this->module = $module;
		$this->list = new LocalObjectList();
	}

	public function loadByID($id){
		$query = "SELECT * FROM proftest_category WHERE CategoryID = " . intval($id);
		$this->LoadFromSQL($query);
	}

	public function save(LocalObject $request){
		$stmt = GetStatement();

		if (!$this->validate($request)){
			return false;
		}

		if ($request->GetIntProperty("CategoryID") > 0){
			$op = "UPDATE `proftest_category` SET";
			$where =  " WHERE CategoryID = " . $request->GetIntProperty("CategoryID");
		}
		else{
			$op = "INSERT INTO `proftest_category` SET ";
			$where = '';
		}

		$query = $op . "
			`ProftestID` = " . $request->GetPropertyForSQL("ProftestID") . ",
			`Title` = " . $request->GetPropertyForSQL("Title") . ",
			`Profession` = " . $request->GetPropertyForSQL("Profession") . ",
			`Subjects` = " . $request->GetPropertyForSQL("Subjects") . ",
			`SortOrder` = " . $request->GetIntProperty("SortOrder") .
			$where;

		if (!$stmt->Execute($query)){
			$this->AddError('category-save-error', $this->module);
			return false;
		}
		return true;
	}

	public function validate(LocalObject $request){
		$this->ClearErrors();
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

		$query = "DELETE FROM proftest_category WHERE CategoryID = " . intval($id);
		$stmt->Execute($query);
	}

//List
	public function loadList($proftest){
		$this->list->LoadFromSQL("SELECT * FROM proftest_category WHERE ProftestID = " . intval($proftest));
	}

	public function LoadListForSuggest(LocalObject $request){
		$this->list->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}

		$query = "SELECT `CategoryID` AS `value`, `Title` AS `label` FROM `proftest_category`
			WHERE INSTR(`Title`, $term)";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND `CategoryID` NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}
		if ($request->IsPropertySet('ProftestID')){
			$query .= " AND ProftestID = " . $request->GetIntProperty('ProftestID');
		}

		$this->list->SetItemsOnPage(0);
		$this->list->LoadFromSQL($query);
	}

	public function loadListByTaskID($taskID){
		$query = "SELECT cat.* FROM proftest_task2category AS task2cat
				  LEFT JOIN proftest_category AS cat ON task2cat.CategoryID = cat.CategoryID
				  WHERE task2cat.TaskID = " . intval($taskID);
		$this->list->LoadFromSQL($query);
	}

	public function getObjectList(){
		return $this->list;
	}

	public function GetItems($selected = array()){
		$result = array();
		foreach ($this->list->_items as $item) {
			$item['Selected'] = in_array($item['CategoryID'], $selected) ? 1 : 0;
			$result[] = $item;
		}

		return $result;
	}
}