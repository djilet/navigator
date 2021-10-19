<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataListList extends LocalObjectList
{
	var $module;

	function DataListList($module = 'data', $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "l.Title ASC",
			"title_desc" => "l.Title DESC",
		));
		$this->SetOrderBy("title_asc");
		$this->SetItemsOnPage(20);
	}

	function LoadListList()
	{
		$where = array();
		$query = "SELECT l.ListID, l.Title, l.Description				
					FROM `data_list` AS l	
					".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			
			//remove items
			$query = "DELETE FROM `data_list_items` WHERE ListID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			//remove lists
			$query = "DELETE FROM `data_list` WHERE ListID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("list-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}

}

?>