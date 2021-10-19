<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataAreaList extends LocalObjectList
{
	var $module;
	var $params;

	function DataAreaList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "a.Title ASC",
			"title_desc" => "a.Title DESC",
			"sortorder_asc" => "a.SortOrder ASC",
			"sortorder_desc" => "a.SortOrder DESC",
		));

		$this->params = array("Area" => array());
		$this->params["Area"] = LoadImageConfig("AreaImage", $this->module, DATA_AREA_IMAGE);
		$this->SetOrderBy("sortorder_asc");
	}

	function LoadAreaList()
	{
		$where = array();
	
		$query = "SELECT a.AreaID,  a.Title, a.AreaImage, a.AreaImageConfig, a.SortOrder 				
					FROM `data_area` AS a			
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
		$this->_PrepareContentBeforeShow();
	}
	
	function LoadForSelection($areaID)
	{
		$query = "SELECT a.AreaID,  a.Title, (CASE a.AreaID WHEN ".intval($areaID)." THEN 1 ELSE 0 END) as Selected FROM `data_area` AS a";
		$this->LoadFromSQL($query);
	}

	function _PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			foreach ($this->params as $k => $v)
			{
				PrepareImagePath($this->_items[$i], $k, $v, "area/");
			}
		}
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			$query = "SELECT * FROM `data_area` 
						WHERE AreaID IN(".implode(", ", Connection::GetSQLArray($ids)).")";
			$result = $stmt->FetchList($query);
			for ($i = 0; $i < count($result); $i++)
			{
				foreach ($this->params as $k => $v)
				{
					if ($result[$i][$k.'Image'])
					{
						@unlink(DATA_IMAGE_DIR."area/".$result[$i][$k."Image"]);
					}
				}
			}
			
			$query = "DELETE FROM `data_area` WHERE AreaID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("area-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}
}

?>