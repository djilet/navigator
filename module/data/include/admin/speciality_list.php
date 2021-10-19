<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataSpecialityList extends LocalObjectList
{
	var $module;
	
	function DataSpecialityList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "s.Title ASC",
			"title_desc" => "s.Title DESC",
		));

		$this->SetOrderBy("title_asc");
	}

	function LoadSpecialityList($universityID)
	{
		$where = array();
		$where[] = "s.UniversityID=".intval($universityID);
		$query = "SELECT s.SpecialityID, s.Title 				
					FROM `data_speciality` AS s			
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
	}
	
	public function LoadForSuggest(LocalObject $request)
	{
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}
	
		$query = "SELECT s.SpecialityID AS `value`, CONCAT(s.Title,' : ',u.Title) AS `label` 
			FROM `data_speciality` AS s
			LEFT JOIN `data_university` AS u ON u.UniversityID=s.UniversityID
			WHERE (INSTR(s.Title, $term) OR INSTR(u.Title, $term))";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND s.SpecialityID NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}
		
		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			
			$query = "DELETE FROM `data_ege` WHERE SpecialityID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			$query = "DELETE FROM `data_speciality` WHERE SpecialityID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("speciality-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}
}

?>