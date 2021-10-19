<?php

class CollegeList extends LocalObjectList{
	protected $module;
	public $single;

	public function __construct($module = 'college'){
		parent::LocalObjectList();
		$this->module = $module;
		$this->single = New LocalObject();
	}

	public function LoadList(){
		$where = array();
		$query = "SELECT *
					FROM `college_list`
					".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "") .
					" ORDER BY SortOrder ASC";

		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}

	public function loadForCollegeList($baseURL, $selectedListID){
		$query = "SELECT l.ListID, l.Title,
        	CASE WHEN l.StaticPath IS NOT NULL AND l.StaticPath<>'' THEN CONCAT(".Connection::GetSQLString($baseURL).", '/', l.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') ELSE CONCAT(".Connection::GetSQLString($baseURL.HTML_EXTENSION).", '?ListID=', l.ListID) END AS ListURL,
        	IF(l.ListID = ".intval($selectedListID).", 1, 0) as Selected
            FROM `college_list` AS l
        	WHERE l.Public='Y' 	
        	ORDER BY l.SortOrder ASC";
		$this->LoadFromSQL($query);
	}

	public function getInfo($listID){
		$stmt = GetStatement();
		return $stmt->FetchRow("SELECT Title, Description, MetaTitle, MetaDescription FROM `college_list` WHERE ListID=".intval($listID));
	}

	public function getFilterArray($listID){
		$result = array();
		$stmt = GetStatement();
		$filterList = $stmt->FetchList("SELECT FilterName, FilterValue FROM `college_list_filter` WHERE ListID=".intval($listID));
		for($i=0; $i<count($filterList); $i++) {
			$result[$filterList[$i]["FilterName"]] = json_decode($filterList[$i]["FilterValue"]);
		}
		return $result;
	}

	public function getIDByStaticPath($staticPath){
		$stmt = GetStatement();
		return $stmt->FetchField("SELECT ListID FROM `college_list` WHERE StaticPath=".Connection::GetSQLString($staticPath));
	}

	public function Remove($ids){
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();

			//remove items
			$query = "DELETE FROM `college_list_filter` WHERE ListID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			//remove lists
			$query = "DELETE FROM `college_list` WHERE ListID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);

			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("list-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}

//Single
	function LoadByID($id){
		$query = "SELECT * 
			FROM `college_list`
			WHERE ListID = " . intval($id);
		$this->single->LoadFromSQL($query);

		if ($this->single->GetProperty("ListID")) {
			return true;
		}
		else{
			return false;
		}
	}

	function Save(){
		$result1 = $this->validate();

		if (!$result1) {
			return false;
		}

		$stmt = GetStatement();

		if ($this->single->GetIntProperty("ListID") > 0) {
			$query = "UPDATE ";
			$where = " WHERE ListID = " . $this->single->GetIntProperty("ListID");
		}
		else {
			$query = "INSERT INTO ";
		}

		$query .= "`college_list` SET 
					Title=".$this->single->GetPropertyForSQL("Title").", 
					Description=".$this->single->GetPropertyForSQL("Description").",
					StaticPath=".$this->single->GetPropertyForSQL("StaticPath").", 
					MetaTitle=".$this->single->GetPropertyForSQL("MetaTitle").",
					MetaDescription=".$this->single->GetPropertyForSQL("MetaDescription").",
					Public=".$this->single->GetPropertyForSQL("Public") . ",
					SortOrder=".$this->single->GetPropertyForSQL("SortOrder") .
					(!empty($where) ? $where : '');

		if ($stmt->Execute($query)){
			if (!$this->single->GetIntProperty("ListID") > 0){
				$this->single->SetProperty("ListID", $stmt->GetLastInsertID());
			}

			foreach (College::getFilterList() as $key => $item) {
				$this->SaveFilterObjects($item, $this->single->GetProperty('Filter' . $item));
			}

			return true;
		}
		else {
			$this->single->AddError("sql-error");
			return false;
		}
	}

	function validate(){
		if ($this->single->GetProperty("Public") != "Y")
			$this->single->SetProperty("Public", "N");

		if(!$this->single->ValidateNotEmpty("Title"))
			$this->single->AddError("list-title-empty", $this->module);

		if(!$this->single->ValidateInt("SortOrder"))
			$this->single->AddError("list-sort-order-not-numeric", $this->module);

		return !$this->single->HasErrors();
	}

	public function SaveFilterObjects($name, $object){
		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `college_list_filter` WHERE `ListID` = ".$this->single->GetIntProperty('ListID') . " AND FilterName=".Connection::GetSQLString($name));
		if(($name == "Region" || $name == "CollegeBigDirection" || $name == "AdmissionBase") && is_array($object) && count($object) > 0) {
			$filterValue = json_encode($object);
			$query = "INSERT INTO `college_list_filter`(ListID,FilterName,FilterValue) VALUES(".$this->single->GetIntProperty('ListID').",".Connection::GetSQLString($name).", ".Connection::GetSQLString($filterValue).")";
			$stmt->Execute($query);
		}
	}

	public function GetFilterObjects($name){
		$stmt = GetStatement();
		$query = "SELECT f.FilterValue FROM `college_list_filter` AS f
			WHERE f.ListID=".$this->single->GetIntProperty('ListID')." AND f.FilterName=".Connection::GetSQLString($name);
		$filterValue = $stmt->FetchRow($query);
		if($filterValue){
			$filterValueArray = json_decode($filterValue["FilterValue"]);
			if(count($filterValueArray) > 0){
				if($name == "Region"){
					$query = "SELECT r.RegionID, r.Title FROM `data_region` AS r WHERE r.RegionID IN (".implode(",",$filterValueArray).") ORDER BY r.Title";
					return $stmt->FetchList($query);
				}
				else if($name == "CollegeBigDirection"){
					$query = "SELECT bd.CollegeBigDirectionID, bd.Title FROM `college_bigdirection` AS bd WHERE bd.CollegeBigDirectionID IN (".implode(",",$filterValueArray).") ORDER BY bd.Title";
					return $stmt->FetchList($query);
				}
				else if($name == "AdmissionBase"){
					$query = "SELECT bd.AdmissionBaseID, bd.Title FROM `college_admission_base` AS bd WHERE bd.AdmissionBaseID IN (".implode(",",$filterValueArray).") ORDER BY bd.Title";
					return $stmt->FetchList($query);
				}
			}
		}
	}
}