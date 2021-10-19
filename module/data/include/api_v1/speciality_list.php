<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobjectlist.php");

class DataSpecialityList extends LocalObjectList
{
	private $module;

	public function __construct($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "sp.Title",
			"custom" => "sp.SortOrder DESC,sp.Title ASC",
		));
		$this->SetOrderBy("custom");
	}

	public function LoadSpecialityList($regionID, $directionID, $universityID, $scores)
	{
		$simpleSort = false;
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
				if(count($universityID) == 1){
					$simpleSort = true;
				}
			}
			else {
				$where[] = "sp.UniversityID=".intval($universityID);
				$simpleSort = true;
			}
		}
		if($simpleSort){
			$this->SetOrderBy("title_asc");
		}
		else {
			$this->SetOrderBy("custom");
		}
		$query = "SELECT sp.SpecialityID, sp.Title, u.UniversityID, u.ShortTitle, 
					sp.Score2016 as Score, 
					sp.AvgScore2016 as AvgScore, 
					count(e.EgeID) as SubjectsCount, 
					GROUP_CONCAT(e.SubjectID) as Subjects, 
					GROUP_CONCAT(e.Score) as SubjectsScore,
					sp.Additional1, sp.Additional2, sp.Additional3, sp.Additional4, sp.Additional5, sp.Additional6, sp.Additional7, sp.Additional8, sp.Additional9, sp.Additional10, 
					d.Title AS DirectionTitle
					FROM `data_speciality` AS sp
					LEFT JOIN data_university u ON sp.UniversityID=u.UniversityID 
					LEFT JOIN data_direction d ON d.DirectionID=sp.DirectionID
					LEFT JOIN data_ege e ON sp.SpecialityID=e.SpecialityID 
					".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")."
					GROUP BY sp.SpecialityID";
		$this->SetItemsOnPage(1000);
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
		
		if($scores && count($scores) > 0){
			$avgscore = 0;
			$scorecount = 0;
			foreach($scores as $subjectID=>$score){
				if($score > 0){
					$avgscore += $score;
					$scorecount += 1;
				}
			}
			if($scorecount > 0){
				$avgscore /= $scorecount;
			}
			
			$result = array();
			for ($i = 0; $i < count($this->_items); $i++)
			{
				if($avgscore > 0 && ($avgscore < $this->_items[$i]["AvgScore"])){
					continue;
				}
				if($this->_items[$i]["Subjects"]){
					$subjects = array_map('intval', explode(',', $this->_items[$i]["Subjects"]));
					$subjectsScore = array_map('intval', explode(',', $this->_items[$i]["SubjectsScore"]));
					$scoreMap = array();
					for($k = 0; $k < count($subjects); $k++){
						$scoreMap[$subjects[$k]] = $subjectsScore[$k];
					}
					$success = true;
					foreach($scores as $subjectID=>$score){
						if(isset($scoreMap[$subjectID]) && $scoreMap[$subjectID] > $score){
							$success = false;
						}
					}
					if($success){
						$result[] = $this->_items[$i];
					}
				}
				else {
					$result[] = $this->_items[$i];
				}
			}
			$this->_items = $result;
		}
		
		for ($i = 0; $i < count($this->_items); $i++)
		{
			$additionalCount = 0;
			if(strlen($this->_items[$i]["Additional1"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional2"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional3"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional4"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional5"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional6"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional7"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional8"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional9"])) $additionalCount += 1;
			if(strlen($this->_items[$i]["Additional10"])) $additionalCount += 1;
			$this->_items[$i]["AdditionalCount"] = $additionalCount;
			unset($this->_items[$i]["Additional1"]);
			unset($this->_items[$i]["Additional2"]);
			unset($this->_items[$i]["Additional3"]);
			unset($this->_items[$i]["Additional4"]);
			unset($this->_items[$i]["Additional5"]);
			unset($this->_items[$i]["Additional6"]);
			unset($this->_items[$i]["Additional7"]);
			unset($this->_items[$i]["Additional8"]);
			unset($this->_items[$i]["Additional9"]);
			unset($this->_items[$i]["Additional10"]);
		}
	}
}