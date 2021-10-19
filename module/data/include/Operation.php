<?php
class Operation extends LocalObjectList
{
	protected $OperationID;
	protected $OperationTitle;
	protected $module = 'data';

//List
	//Load
		public function load()
		{
			$query = 'SELECT * FROM `data_profession_operation` ORDER BY `OperationTitle` ASC';
			$this->LoadFromSQL($query);
		}

		public function LoadForSuggest(LocalObject $request){
			$this->_items = array();
			$term = $request->GetPropertyForSQL('term');
			if (empty($term)) {
				return;
			}

			$query = "SELECT `OperationID` AS `value`, `OperationTitle` AS `label` FROM `data_profession_operation`
				WHERE INSTR(`OperationTitle`, $term)";
			$itemIDs = $request->GetProperty('ItemIDs');
			if($itemIDs) {
				$query .= " AND `OperationID` NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
			}

			$this->SetItemsOnPage(0);
			$this->LoadFromSQL($query);
		}

	//Get
		public function getItems($selected = array())
		{
			$result = array();
			foreach ($this->_items as $item) {
				if (!empty($selected)){
					$item['Selected'] = in_array($item['OperationID'], $selected) ? 1 : 0;
				}

				$result[] = $item;
			}

			return $result;
		}

//Single
	//Load
	public function loadByID($id){
		$stmt = GetStatement();

		$query = "SELECT * FROM data_profession_operation WHERE OperationID = ".Connection::GetSQLString($id);
		if( $result = $stmt->FetchRow($query) ){
			$this->OperationID = $result['OperationID'];
			$this->OperationTitle = $result['OperationTitle'];
			return true;
		}
		return false;
	}

	public function loadByTitle($title){
		$stmt = GetStatement();

		$query = "SELECT * FROM data_profession_operation WHERE OperationTitle = ".Connection::GetSQLString($title);
		if( $result = $stmt->FetchRow($query) ){
			$this->OperationID = $result['OperationID'];
			$this->OperationTitle = $result['OperationTitle'];
			return true;
		}
		return false;
	}

	//Get
	public function getItem(){
		return new LocalObject(array(
			'OperationID' => $this->OperationID,
			'OperationTitle' => $this->OperationTitle
		));
	}

	//CRUD
	public function save($OperationID, $OperationTitle){
		$stmt = GetStatement();
		//Insert
		if (!empty($OperationTitle)){
			if ($OperationID > 0) {
				$query = "UPDATE `data_profession_operation` SET
						OperationTitle=" . Connection::GetSQLString($OperationTitle) . "
						WHERE OperationID = " . intval( $OperationID );
			}
			else{
				$query = "INSERT INTO `data_profession_operation` SET
						OperationTitle=" . Connection::GetSQLString($OperationTitle);
			}

			if( $stmt->Execute($query) ){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			$this->AddError("operation-title-empty", $this->module);
		}
	}

	public function remove($OperationID){
		$stmt = GetStatement();

		$query = "DELETE FROM data_profession_operation WHERE OperationID = " . intval($OperationID);
		if ($stmt->Execute($query)){
			$this->AddMessage("operation-removed", $this->module);
			return true;
		}
		else{
			return false;
		}
	}
}