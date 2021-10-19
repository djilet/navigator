<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataRegionList extends LocalObjectList
{
	var $module;
	var $params;

	function DataRegionList($module = 'data', $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "r.Title ASC",
			"title_desc" => "r.Title DESC",
			"sortorder_asc" => "r.SortOrder ASC",
			"sortorder_desc" => "r.SortOrder DESC",
		));

		$this->params = array("Region" => array());
		$this->params["Region"] = LoadImageConfig("RegionImage", $this->module, DATA_REGION_IMAGE);
		$this->SetOrderBy("title_asc");
	}

	function LoadRegionList()
	{
		$where = array();
	
		$query = "SELECT r.RegionID,  r.Title, r.RegionImage, r.RegionImageConfig, r.SortOrder 				
					FROM `data_region` AS r			
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
		$this->_PrepareContentBeforeShow();
	}
	
	function LoadForSelection($regionID=0)
	{
		$query = "SELECT r.RegionID,  r.Title, (CASE r.RegionID WHEN ".intval($regionID)." THEN 1 ELSE 0 END) as Selected FROM `data_region` AS r";
		$this->LoadFromSQL($query);
	}
	
	public function LoadForSuggest(LocalObject $request)
	{
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}
	
		$query = "SELECT r.RegionID AS `value`, r.Title AS `label`
			FROM `data_region` AS r
			WHERE (INSTR(r.Title, $term))";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND r.RegionID NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}
	
		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
	}

	function _PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			foreach ($this->params as $k => $v)
			{
				PrepareImagePath($this->_items[$i], $k, $v, "region/");
			}
		}
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			$query = "SELECT * FROM `data_region` 
						WHERE RegionID IN(".implode(", ", Connection::GetSQLArray($ids)).")";
			$result = $stmt->FetchList($query);
			for ($i = 0; $i < count($result); $i++)
			{
				foreach ($this->params as $k => $v)
				{
					if ($result[$i][$k.'Image'])
					{
						@unlink(DATA_IMAGE_DIR."region/".$result[$i][$k."Image"]);
					}
				}
			}
			
			$query = "DELETE FROM `data_region` WHERE RegionID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("region-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}
}

?>