<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataBigDirectionList extends LocalObjectList
{
	var $module;
	
	function DataBigDirectionList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "bd.Title ASC",
			"title_desc" => "bd.Title DESC",
			"sortorder_asc" => "bd.SortOrder ASC",
			"sortorder_desc" => "bd.SortOrder DESC",
		));

		$this->SetOrderBy("sortorder_asc");
	}

	function LoadForSelection($bigDirectionID)
	{
		$query = "SELECT bd.BigDirectionID, bd.Title, (CASE bd.BigDirectionID WHEN ".intval($bigDirectionID)." THEN 1 ELSE 0 END) as Selected FROM `data_bigdirection` AS bd";
		$this->LoadFromSQL($query);
	}
	
	public function LoadForSuggest(LocalObject $request)
	{
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}
	
		$query = "SELECT bd.BigDirectionID AS `value`, bd.Title AS `label` FROM `data_bigdirection` AS bd
			WHERE INSTR(bd.Title, $term)";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND bd.BigDirectionID NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}
	
		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
	}
}

?>