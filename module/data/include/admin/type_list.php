<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataTypeList extends LocalObjectList
{
	var $module;
	var $params;

	function DataTypeList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "t.Title ASC",
			"title_desc" => "t.Title DESC",
			"sortorder_asc" => "t.SortOrder ASC",
			"sortorder_desc" => "t.SortOrder DESC",
		));

		$this->params = array("Type" => array());
		$this->params["Type"] = LoadImageConfig("TypeImage", $this->module, DATA_TYPE_IMAGE);
		$this->SetOrderBy("sortorder_asc");
	}

	function LoadTypeList()
	{
		$where = array();
	
		$query = "SELECT t.TypeID,  t.Title, t.TypeImage, t.TypeImageConfig, t.SortOrder 				
					FROM `data_type` AS t			
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
		$this->_PrepareContentBeforeShow();
	}
	
	function LoadForSelection($typeID=0)
	{
		$query = "SELECT t.TypeID,  t.Title, (CASE t.TypeID WHEN ".intval($typeID)." THEN 1 ELSE 0 END) as Selected FROM `data_type` AS t";
		$this->LoadFromSQL($query);
	}

	function _PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			foreach ($this->params as $k => $v)
			{
				PrepareImagePath($this->_items[$i], $k, $v, "type/");
			}
		}
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			$query = "SELECT * FROM `data_type` 
						WHERE TypeID IN(".implode(", ", Connection::GetSQLArray($ids)).")";
			$result = $stmt->FetchList($query);
			for ($i = 0; $i < count($result); $i++)
			{
				foreach ($this->params as $k => $v)
				{
					if ($result[$i][$k.'Image'])
					{
						@unlink(DATA_IMAGE_DIR."type/".$result[$i][$k."Image"]);
					}
				}
			}
			
			$query = "DELETE FROM `data_type` WHERE TypeID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("type-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}
}

?>