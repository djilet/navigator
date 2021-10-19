<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobjectlist.php");

class DataTypeList extends LocalObjectList
{
	var $module;

	public function DataTypeList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"sort_asc" => "t.SortOrder ASC",
			"sort_desc" => "t.SortOrder DESC",
			"title_asc" => "t.Title ASC",
			"title_desc" => "t.Title DESC",
		));
		$this->SetOrderBy("title_asc");
	}

	public function LoadTypeList($regionID)
	{
		$where = array();
		if($regionID){
			if(is_array($regionID)){
				$where[] = "u.RegionID IN (".implode(", ", $regionID).")";
			}
			else {
				$where[] = "u.RegionID=".intval($regionID);
			}
		}
		$query = "SELECT t.TypeID, t.Title, t.SortOrder, t.TypeImage, t.TypeImageConfig 
			FROM `data_type` AS t 
			LEFT JOIN `data_university` u ON t.TypeID=u.TypeID 
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")." 
			GROUP BY t.TypeID";
		$this->LoadFromSQL($query);
		$this->PrepareContentBeforeShow();
	}

	private function PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			if($this->_items[$i]["TypeImage"])
				$this->_items[$i]["TypeImageURL"] = DATA_IMAGE_URL_PREFIX."type/".$this->_items[$i]["TypeImage"];
			else
				$this->_items[$i]["TypeImageURL"] = null;
			unset($this->_items[$i]["TypeImage"]);	
		}
	}
}

?>