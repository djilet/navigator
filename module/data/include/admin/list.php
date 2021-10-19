<?php
require_once(dirname(__FILE__)."/../../init.php"); 
 
es_include("localobject.php");

class DataList extends LocalObject
{
	var $module;
	var $params;

	function __construct($module = 'data', $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
	}

	function LoadByID($id)
	{
		$query = "SELECT l.ListID, l.Title, l.Type, l.Description, l.StaticPath, l.MetaTitle, l.MetaDescription, l.Public
			FROM `data_list` AS l 
			WHERE l.ListID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);
		
		if ($this->GetProperty("ListID"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function Save()
	{
		$result1 = $this->Validate();
	    
		if (!$result1)
		{
		    return false;
		}

		$stmt = GetStatement();
		
		if ($this->GetIntProperty("ListID") > 0)
		{
			$query = "UPDATE `data_list` SET 
					Title=".$this->GetPropertyForSQL("Title").", 
					Description=".$this->GetPropertyForSQL("Description").",
					StaticPath=".$this->GetPropertyForSQL("StaticPath").", 
					MetaTitle=".$this->GetPropertyForSQL("MetaTitle").",
					MetaDescription=".$this->GetPropertyForSQL("MetaDescription").",
					Public=".$this->GetPropertyForSQL("Public")."
				WHERE ListID=".$this->GetIntProperty("ListID");
		}
		else
		{
 			$query = "INSERT INTO `data_list` SET
					Title=".$this->GetPropertyForSQL("Title").",
					Type=".$this->GetPropertyForSQL("Type").", 
					Description=".$this->GetPropertyForSQL("Description").",
					StaticPath=".$this->GetPropertyForSQL("StaticPath").", 
					MetaTitle=".$this->GetPropertyForSQL("MetaTitle").",
					MetaDescription=".$this->GetPropertyForSQL("MetaDescription").",
					Public=".$this->GetPropertyForSQL("Public");
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("ListID") > 0)
				$this->SetProperty("ListID", $stmt->GetLastInsertID());
			
			$this->SaveLinkedObjects("university", $this->GetProperty('LinkedUniversity'));
			$this->SaveLinkedObjects("speciality", $this->GetProperty('LinkedSpeciality'));
			$this->SaveLinkedObjects("profession", $this->GetProperty('LinkedProfession'));
			
			$this->SaveFilterObjects("Region", $this->GetProperty('FilterRegion'));
			$this->SaveFilterObjects("BigDirection", $this->GetProperty('FilterBigDirection'));
			
			return true;
		}
		else
		{
			$this->AddError("sql-error");
			return false;
		}
	}
	
	function Validate()
	{
		if ($this->GetProperty("Public") != "Y")
			$this->SetProperty("Public", "N");
		
		if(!$this->ValidateNotEmpty("Title"))
			$this->AddError("list-title-empty", $this->module);
		
		return !$this->HasErrors();
	}
	
	public function SaveLinkedObjects($type, $ids)
	{
		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_list_item` WHERE TargetType=".Connection::GetSQLString($type)." AND `ListID` = ".$this->GetIntProperty('ListID'));
		if (!is_array($ids)) {
			return;
		}
		foreach ($ids as $id) {
			$query = "INSERT INTO `data_list_item`(ListID,TargetType,TargetID) VALUES(".$this->GetIntProperty('ListID').",".Connection::GetSQLString($type).", ".intval($id).")";
			$stmt->Execute($query);
		}
	}
	
	public function GetLinkedObjects($type)
	{
		$stmt = GetStatement();
		if($type == "university"){
			$query = "SELECT u.UniversityID, u.Title FROM `data_university` AS u
				INNER JOIN `data_list_item` AS li ON li.TargetID=u.UniversityID AND li.TargetType='university'
				WHERE li.ListID=".$this->GetIntProperty('ListID');
			return $stmt->FetchList($query);
		}
		else if($type == "speciality"){
			$query = "SELECT s.SpecialityID, CONCAT(s.Title,' : ',u.Title) as Title FROM `data_speciality` AS s
				LEFT JOIN `data_university` AS u ON s.UniversityID=u.UniversityID
				INNER JOIN `data_list_item` AS li ON li.TargetID=s.SpecialityID AND li.TargetType='speciality'
				WHERE li.ListID=".$this->GetIntProperty('ListID');
			return $stmt->FetchList($query);
		}
		else if($type == "profession"){
			$query = "SELECT p.ProfessionID, p.Title FROM `data_profession` AS p
				INNER JOIN `data_list_item` AS li ON li.TargetID=p.ProfessionID AND li.TargetType='profession'
				WHERE li.ListID=".$this->GetIntProperty('ListID');
			return $stmt->FetchList($query);
		}
		return false;
	}
	
	public function SaveFilterObjects($name, $object)
	{
		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_list_filter` WHERE `ListID` = ".$this->GetIntProperty('ListID')." AND FilterName=".Connection::GetSQLString($name));
		if(($name == "Region" || $name == "BigDirection") && is_array($object) && count($object) > 0) {
			$filterValue = json_encode($object);
			$query = "INSERT INTO `data_list_filter`(ListID,FilterName,FilterValue) VALUES(".$this->GetIntProperty('ListID').",".Connection::GetSQLString($name).", ".Connection::GetSQLString($filterValue).")";
			$stmt->Execute($query);
		}
	}
	
	public function GetFilterObjects($name)
	{
		$result = array();
		$stmt = GetStatement();
		$query = "SELECT f.FilterValue FROM `data_list_filter` AS f
			WHERE f.ListID=".$this->GetIntProperty('ListID')." AND f.FilterName=".Connection::GetSQLString($name);
		$filterValue = $stmt->FetchRow($query);
		if($filterValue){
			$filterValueArray = json_decode($filterValue["FilterValue"]);
			if(count($filterValueArray) > 0){
				if($name == "Region"){
					$query = "SELECT r.RegionID, r.Title FROM `data_region` AS r WHERE r.RegionID IN (".implode(",",$filterValueArray).") ORDER BY r.Title";
					return $stmt->FetchList($query);
				}
				else if($name == "BigDirection"){
					$query = "SELECT bd.BigDirectionID, bd.Title FROM `data_bigdirection` AS bd WHERE bd.BigDirectionID IN (".implode(",",$filterValueArray).") ORDER BY bd.Title";
					return $stmt->FetchList($query);
				}
			}
		}
	}
}

