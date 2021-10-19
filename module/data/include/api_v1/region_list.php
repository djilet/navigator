<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobjectlist.php");

class DataRegionList extends LocalObjectList
{
	var $module;

	public function DataRegionList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"sort_asc" => "r.SortOrder ASC",
			"sort_desc" => "r.SortOrder DESC",
			"title_asc" => "r.Title ASC",
			"title_desc" => "r.Title DESC",
		));
		$this->SetOrderBy("title_asc");
	}

	public function LoadRegionList($areaID)
	{
		$where = array();
		if($areaID){
			if(is_array($areaID)){
				$where[] = "r.AreaID IN (".implode(", ", $areaID).")";
			}
			else {
				$where[] = "r.AreaID=".intval($areaID);
			}
		}
		$query = "SELECT r.RegionID, r.AreaID, r.Title, r.SortOrder, r.RegionImage, r.RegionImageConfig FROM `data_region` AS r		
					".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");
		$this->LoadFromSQL($query);
		$this->PrepareContentBeforeShow();
	}

	private function PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			if($this->_items[$i]["RegionImage"])
				$this->_items[$i]["RegionImageURL"] = DATA_IMAGE_URL_PREFIX."region/".$this->_items[$i]["RegionImage"];
			else
				$this->_items[$i]["RegionImageURL"] = null;
			unset($this->_items[$i]["RegionImage"]);	
		}
	}
}

?>