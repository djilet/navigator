<?php

Class AdmissionBase extends LocalObjectList{
	protected $admissionBaseID;
	protected $Title;
	protected $SortOrder;
	protected $module;

	public function __construct($module = 'college'){
		parent::LocalObjectList();
		$this->module = $module;
	}

	public function load()
	{
		$query = 'SELECT * FROM `college_admission_base` ORDER BY `SortOrder` ASC';
		$this->LoadFromSQL($query);
	}

	public function LoadForSuggest(LocalObject $request){
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}

		$query = "SELECT ab.AdmissionBaseID AS `value`, ab.Title AS `label` FROM `college_admission_base` AS ab
			WHERE INSTR(ab.Title, $term)";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND ab.AdmissionBaseID NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}

		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
		//echo $query;
	}

	public function getItems($selected = array())
	{
		$result = array();
		foreach ($this->_items as $item) {
			if (!empty($selected)){
				$item['Selected'] = in_array($item['AdmissionBaseID'], $selected) ? 1 : 0;
			}

			$result[] = $item;
		}

		return $result;
	}

//Static
	public static function getMaxSortOrder(){
		$stmt = GetStatement();
		if($result = $stmt->FetchField('SELECT MAX(`SortOrder`) +1 FROM `college_admission_base`')){
			return $result;
		}
		else{
			return 0;
		}
	}

//Single
	//Load
	public function loadByID($id){
		$stmt = GetStatement();

		$query = "SELECT * FROM college_admission_base WHERE AdmissionBaseID = ".Connection::GetSQLString($id);
		if( $result = $stmt->FetchRow($query) ){
			$this->admissionBaseID = $result['AdmissionBaseID'];
			$this->Title = $result['Title'];
			$this->SortOrder = $result['SortOrder'];
			return true;
		}
		return false;
	}

	//Get
	public function getItem(){
		return new LocalObject(array(
			'AdmissionBaseID' => $this->admissionBaseID,
			'Title' => $this->Title,
			'SortOrder' => $this->SortOrder
		));
	}

	//CRUD
	public function save($AdmissionBaseID, $Title, $sortOrder = 0){
		$stmt = GetStatement();
		//Insert
		if (empty($Title)){
			$this->AddError("admission-base-title-empty", $this->module);
			return false;
		}

		if (!is_numeric($sortOrder) || $sortOrder < 0){
			$this->AddError("sort-order-not-numeric", $this->module);
			return false;
		}

		if ($AdmissionBaseID > 0) {
			$query = "UPDATE `college_admission_base` SET
							Title=" . Connection::GetSQLString($Title) . ",
							SortOrder=" . intval($sortOrder) . "
							WHERE AdmissionBaseID = " . intval( $AdmissionBaseID );
		}
		else{
			$query = "INSERT INTO `college_admission_base` SET
							Title=" . Connection::GetSQLString($Title) . ",
							SortOrder=" . intval($sortOrder);
		}

		if( $stmt->Execute($query) ){
			return true;
		}
		else{
			return false;
		}
	}

	public function remove($AdmissionBaseID){
		$stmt = GetStatement();

		$query = "DELETE FROM college_admission_base WHERE AdmissionBaseID = " . intval($AdmissionBaseID);
		if ($stmt->Execute($query)){
			$this->AddMessage("admission-base-removed", $this->module);
			return true;
		}
		else{
			return false;
		}
	}
}