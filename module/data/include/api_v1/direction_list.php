<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobjectlist.php");

class DataDirectionList extends LocalObjectList
{
	private $module;

	public function __construct($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"sort_asc" => "d.SortOrder ASC",
			"sort_desc" => "d.SortOrder DESC",
			"title_asc" => "d.Title ASC",
			"title_desc" => "d.Title DESC",
		));
		$this->SetOrderBy("title_asc");
	}

	public function LoadDirectionList($universityID, $directionID, $withProfession = false, $search = null)
	{
		$stmt = GetStatement();
		
		$where = array();
		if($universityID){
			if(is_array($universityID)){
				$where[] = "s.UniversityID IN (".implode(", ", $universityID).")";
			}
			else {
				$where[] = "s.UniversityID=".intval($universityID);
			}
		}
		if ($directionID) {
			if (is_array($directionID)) {
				$where[] = "d.DirectionID IN (".implode(", ", Connection::GetSQLArray($directionID)).")";
			} else {
				$where[] = "d.DirectionID=".intval($directionID);
			}
		}
		if ($search != null) {
		    $where[] = "LOWER(d.Title) LIKE ".Connection::GetSQLString("%".strtolower($search)."%");
		}
		
		
		$query = "SELECT d.DirectionID, d.Title, d.SortOrder FROM `data_direction` AS d
					".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")."
					ORDER BY d.Title ASC";
		$list = $stmt->FetchIndexedList($query, 'DirectionID');
		
		if ($withProfession) {
			$where = array();

			if ($directionID) {
				if (is_array($directionID)) {
					$where[] = "p2d.DirectionID IN (".implode(", ", Connection::GetSQLArray($directionID)).")";
				} else {
					$where[] = "p2d.DirectionID=".intval($directionID);
				}
			}
			
			$profList = $stmt->FetchList("SELECT p.ProfessionID, p.Title, p.Industry, GROUP_CONCAT(p2d.DirectionID) AS DirectionID
				FROM `data_profession` AS p 
				INNER JOIN `data_profession2direction` AS p2d 
				  ON p.ProfessionID=p2d.ProfessionID
				  ".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")."
				GROUP BY p.ProfessionID");
			if ($profList) {
				foreach ($profList as $item) {
					$directions = explode(',', $item['DirectionID']);
					if ($directions AND is_array($directions)) {
						foreach ($directions as $direction) {
							if (isset($list[$direction])) {
								if (!isset($list[$direction]['ProfessionList'])) {
									$list[$direction]['ProfessionList'] = array();
								}
								$list[$direction]['ProfessionList'][] = $item;
							}
						}
					}
				}
			}
		}
		
		$this->_items = array_values($list);
	}

}