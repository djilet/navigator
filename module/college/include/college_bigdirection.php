<?php

Class CollegeBigDirection extends LocalObjectList{
	protected $CollegeBigDirectionID;
	protected $Title;
	protected $SortOrder;
	protected $module;

	public function __construct($module = 'college'){
		parent::LocalObjectList();
		$this->module = $module;
	}

	public function load()
	{
		$query = 'SELECT * FROM `college_bigdirection` ORDER BY `SortOrder` ASC';
		$this->LoadFromSQL($query);
	}

	public function LoadForSuggest(LocalObject $request){
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}

		$query = "SELECT bd.CollegeBigDirectionID AS `value`, bd.Title AS `label` FROM `college_bigdirection` AS bd
			WHERE INSTR(bd.Title, $term)";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND bd.CollegeBigDirectionID NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
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
				$item['Selected'] = in_array($item['CollegeBigDirectionID'], $selected) ? 1 : 0;
			}

			$result[] = $item;
		}

		return $result;
	}

//Static
	public static function getMaxSortOrder(){
		$stmt = GetStatement();
		if($result = $stmt->FetchField('SELECT MAX(`SortOrder`) +1 FROM `college_bigdirection`')){
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

		$query = "SELECT * FROM college_bigdirection WHERE CollegeBigDirectionID = ".Connection::GetSQLString($id);
		if( $result = $stmt->FetchRow($query) ){
			$this->CollegeBigDirectionID = $result['CollegeBigDirectionID'];
			$this->Title = $result['Title'];
			$this->SortOrder = $result['SortOrder'];
			return true;
		}
		return false;
	}

	//Get
	public function getItem(){
		return new LocalObject(array(
			'CollegeBigDirectionID' => $this->CollegeBigDirectionID,
			'Title' => $this->Title,
			'SortOrder' => $this->SortOrder
		));
	}

	//CRUD
	public function save($CollegeBigDirectionID, $Title, $sortOrder = 0){
		$stmt = GetStatement();
		//Insert
		if (empty($Title)){
			$this->AddError("bigdirection-title-empty", $this->module);
			return false;
		}

		if (!is_numeric($sortOrder) || $sortOrder < 0){
			$this->AddError("sort-order-not-numeric", $this->module);
			return false;
		}

		if ($CollegeBigDirectionID > 0) {
			$query = "UPDATE `college_bigdirection` SET
							Title=" . Connection::GetSQLString($Title) . ",
							SortOrder=" . intval($sortOrder) . "
							WHERE CollegeBigDirectionID = " . intval( $CollegeBigDirectionID );
		}
		else{
			$query = "INSERT INTO `college_bigdirection` SET
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

	public function remove($CollegeBigDirectionID){
		$stmt = GetStatement();

		$query = "DELETE FROM college_bigdirection WHERE CollegeBigDirectionID = " . intval($CollegeBigDirectionID);
		if ($stmt->Execute($query)){
			$this->AddMessage("bigdirection-removed", $this->module);
			return true;
		}
		else{
			return false;
		}
	}
}