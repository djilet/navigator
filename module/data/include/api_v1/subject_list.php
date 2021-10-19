<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobjectlist.php");

class DataSubjectList extends LocalObjectList
{
	private $module;

	public function __construct($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "s.Title ASC",
			"title_desc" => "s.Title DESC",
		));
		$this->SetOrderBy("title_asc");
	}

	public function LoadSubjectList($regionID, $directionID, $universityID)
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
				$where[] = "sp.DirectionID IN (".implode(", ", $directionID).")";
			}
			else {
				$where[] = "sp.DirectionID=".intval($directionID);
			}
		}
		if($universityID){
			if(is_array($universityID)){
				$where[] = "sp.UniversityID IN (".implode(", ", $universityID).")";
			}
			else {
				$where[] = "sp.UniversityID=".intval($universityID);
			}
		}
		$query = "SELECT s.SubjectID, s.Title FROM `data_subject` AS s
					LEFT JOIN data_ege e ON s.SubjectID=e.SubjectID 
					LEFT JOIN data_speciality sp ON e.SpecialityID=sp.SpecialityID 
					LEFT JOIN data_university u ON sp.UniversityID=u.UniversityID 
					".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")."
					GROUP BY s.SubjectID";
		$this->LoadFromSQL($query);
	}
}