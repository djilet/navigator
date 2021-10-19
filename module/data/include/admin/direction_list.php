<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataDirectionList extends LocalObjectList
{
	var $module;
	
	function DataDirectionList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "d.Title ASC",
			"title_desc" => "d.Title DESC",
			"sortorder_asc" => "d.SortOrder ASC",
			"sortorder_desc" => "d.SortOrder DESC",
		));

		$this->SetOrderBy("sortorder_asc");
	}

	function LoadDirectionList()
	{
		$where = array();
	
		$query = "SELECT d.DirectionID,  d.Title, d.SortOrder 				
					FROM `data_direction` AS d			
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
	}
	
	function LoadForSelection($directionID)
	{
		$query = "SELECT d.DirectionID, d.Title, (CASE d.DirectionID WHEN ".intval($directionID)." THEN 1 ELSE 0 END) as Selected FROM `data_direction` AS d";
		$this->LoadFromSQL($query);
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			
			$query = "DELETE FROM `data_direction` WHERE DirectionID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("direction-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}
	
	public function LoadForSuggest(LocalObject $request)
	{
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}
	
		$query = "SELECT d.DirectionID AS `value`, d.Title AS `label` FROM `data_direction` AS d
			WHERE INSTR(d.Title, $term)";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND d.DirectionID NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}
	
		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
	}
}

?>