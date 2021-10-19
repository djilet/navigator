<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobjectlist.php");

class DataAreaList extends LocalObjectList
{
	private $module;

	public function __construct($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"sort_asc" => "a.SortOrder ASC",
			"sort_desc" => "a.SortOrder DESC",
			"title_asc" => "a.Title ASC",
			"title_desc" => "a.Title DESC",
		));
		$this->SetOrderBy("title_asc");
	}

	public function LoadAreaList($addRegions)
	{
		// $where = array();
		$query = "SELECT a.AreaID, a.Title, a.SortOrder, a.AreaImage, a.AreaImageConfig FROM `data_area` AS a";	
		//			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");
		$this->LoadFromSQL($query);
		$this->PrepareContentBeforeShow();
		
		if($addRegions){
			$stmt = GetStatement();
			$regions = $stmt->FetchList("SELECT r.RegionID, r.AreaID, r.Title FROM `data_region` AS r ORDER BY r.Title");
			for ($i = 0; $i < count($this->_items); $i++)
			{
				for ($j = 0; $j < count($regions); $j++)
				{
					if($this->_items[$i]["AreaID"] == $regions[$j]["AreaID"]){
						if(!isset($this->_items[$i]["Regions"])){
							$this->_items[$i]["Regions"] = array();
						}
						$this->_items[$i]["Regions"][] = $regions[$j];
					}
				}
			}
		}
	}

	public function LoadAreaWithUniversityList(LocalObject $request)
	{
		$stmt = GetStatement();
		$query = "SELECT a.AreaID, a.Title, a.SortOrder, a.AreaImage, a.AreaImageConfig FROM `data_area` AS a";
		$list = $stmt->FetchIndexedList($query, "AreaID");

		$join = array();
		$where = array();
		
		if ($regions = json_decode($request->GetProperty('RegionID'))) {
			if (is_array($regions)) {
				$where[] = "r.RegionID IN (".implode(',', Connection::GetSQLArray($regions)).')';
			} else {
				$where[] = "r.RegionID=".intval($regions);
			}
		}
		if ($directions = json_decode($request->GetProperty('DirectionID'))) {
			if (is_array($directions)) {
				$where[] = "s.DirectionID IN (".implode(',', Connection::GetSQLArray($directions)).')';
			} else {
				$where[] = "s.DirectionID=".intval($directions);
			}
		}
		if ($request->GetIntProperty('Military') == 1) {
			$where[] = "s.Military='Да'";
		}
		if ($request->GetIntProperty('Delay') == 1) {
			$where[] = "s.Delay='Да'";
		}
		if ($request->GetIntProperty('Hostel') == 1) {
			$where[] = "s.Hostel='Да'";
		}
		if ($title = $request->GetProperty('TitleSearch')) {
			$where[] = '(INSTR(u.Title, '.Connection::GetSQLString($title).') OR INSTR(u.ShortTitle, '.Connection::GetSQLString($title).') )';
		}

		if ($subjects = $request->GetProperty('Subjects')) {
			foreach ($subjects as $subject => $value) {
				$subject = intval($subject);
				$join[] = " INNER JOIN `data_ege` AS e{$subject} ON e{$subject}.`SpecialityID`=s.`SpecialityID` ";
				$where[] = '(e'.$subject.'.`SubjectID`='.$subject.' AND e'.$subject.'.`Score`<='.intval($value).')';
			}
		}

		$query = "SELECT u.`UniversityID`, u.`ShortTitle`, u.`Title`, 
				r.`AreaID`, t.`Title` AS TypeTitle, u.RegionID
            FROM `data_university` AS u
			INNER JOIN `data_region` AS r ON r.`RegionID`=u.`RegionID`
			INNER JOIN `data_area` AS a ON a.`AreaID`=r.`AreaID`
			INNER JOIN `data_speciality` AS s ON s.`UniversityID`=u.`UniversityID`
			LEFT JOIN `data_type` AS t ON t.`TypeID`=u.`TypeID`
			".(!empty($join) ? implode(" \n ", $join) : '')."
			".(!empty($where) ? " WHERE ".implode(' AND ', $where) : '')."
			GROUP BY u.UniversityID
			ORDER BY u.SortOrder DESC,u.Title";
		$universities = $stmt->FetchList($query);
		
		if ($universities) {
			foreach ($universities as $university) {
				if (isset($list[$university['AreaID']])) {
					if (!isset($list[$university['AreaID']]['UniversityList'])) {
						$list[$university['AreaID']]['UniversityList'] = array();
					}

					$list[$university['AreaID']]['UniversityList'][] = $university;
				}
			}
		} else {
			$this->_items = array();
			return;
		}

		foreach ($list as $key => $item) {
			if (empty($item['UniversityList'])) {
				unset($list[$key]);
			}
		}

		$this->_items = array_values($list);
	}

	private function PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			if($this->_items[$i]["AreaImage"])
				$this->_items[$i]["AreaImageURL"] = DATA_IMAGE_URL_PREFIX."area/".$this->_items[$i]["AreaImage"];
			else
				$this->_items[$i]["AreaImageURL"] = null;
			unset($this->_items[$i]["AreaImage"]);	
		}
	}
}

?>