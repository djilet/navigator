<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobjectlist.php");

class DataUniversityList extends LocalObjectList
{
	private $module;

	public function __construct($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "u.Title ASC",
			"title_desc" => "u.Title DESC",
			"custom" => "u.SortOrder DESC,u.Title ASC",
		));
		$this->SetOrderBy("custom");
	}

	public function LoadUniversityList($regionID, $directionID, $typeID)
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
		if($directionID){
			if(is_array($directionID)){
				$where[] = "s.DirectionID IN (".implode(", ", $directionID).")";
			}
			else {
				$where[] = "s.DirectionID=".intval($directionID);
			}
		}
		if($typeID){
			if(is_array($typeID)){
				$where[] = "u.TypeID IN (".implode(", ", $typeID).")";
			}
			else {
				$where[] = "u.TypeID=".intval($typeID);
			}
		}
		$query = "SELECT u.UniversityID, u.ShortTitle, u.Title, u.RegionID, s.DirectionID  FROM `data_university` AS u
					LEFT JOIN data_speciality s ON u.UniversityID=s.UniversityID
					".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")."
					GROUP BY u.UniversityID";
		$this->LoadFromSQL($query);
	}
}