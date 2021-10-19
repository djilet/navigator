<?php

class MarathonMapList extends LocalObjectList {
    
	public function __construct(){
		parent::LocalObjectList();
		$this->SetItemsOnPage(0);
	}

	public function load(){
		$query = "SELECT m.StepID, m.Name, m.DataTable
				  FROM `marathon_map` m
				  ORDER BY m.SortOrder";
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}
	
	public function loadUserAnswers($stepID, $dataTable, $marathonUserIDs){
	    $stmt = GetStatement();
	    $join = array();
	    $value = null;
	    $join[] = "LEFT JOIN `marathon_map2user_answer` a ON m2u.AnswerID=a.AnswerID";
	    
	    switch ($dataTable) {
	        case 'WhoWork':
	            $value = "GROUP_CONCAT(t.WhoWorkTitle separator ', ')";
	            $join[] = "LEFT JOIN data_profession_who_work t ON a.ValueID=t.WhoWorkID";
	            break;
	        case 'WantWork':
	            $value = "GROUP_CONCAT(t.WantWorkTitle separator ', ')";
	            $join[] = "LEFT JOIN data_profession_want_work t ON a.ValueID=t.WantWorkID";
	            break;
	        case 'Industry':
	            $value = "GROUP_CONCAT(t.IndustryTitle separator ', ')";
	            $join[] = "LEFT JOIN data_profession_industry t ON a.ValueID=t.IndustryID";
	            break;
	        case 'Profession':
	            $value = "GROUP_CONCAT(t.Title separator ', ')";
	            $join[] = "LEFT JOIN data_profession t ON a.ValueID=t.ProfessionID";
	            break;
	        case 'Subject':
	            $value = "GROUP_CONCAT(t.Title separator ', ')";
	            $join[] = "LEFT JOIN data_subject t ON a.ValueID=t.SubjectID";
	            break;
	        case 'University':
	            $value = "GROUP_CONCAT(t.ShortTitle separator ', ')";
	            $join[] = "LEFT JOIN data_university t ON a.ValueID=t.UniversityID";
	            break;
	        case 'Region':
	            $value = "GROUP_CONCAT(t.Title separator ', ')";
	            $join[] = "LEFT JOIN data_region t ON a.ValueID=t.RegionID";
	            break;
	        case 'Direction':
	            $value = "GROUP_CONCAT(t.Title separator ', ')";
	            $join[] = "LEFT JOIN data_bigdirection t ON a.ValueID=t.BigDirectionID";
	            break;
	        default:
	            $value = "GROUP_CONCAT(a.ValueID separator ', ')";
	    }
	    
	    $query = "SELECT m2u.MarathonUserID, ".$value." AS Value
				  FROM `marathon_map2user` m2u
                  ".(!empty($join) ? implode(" \n ", $join) : '')."
                  WHERE m2u.StepID=".intval($stepID)." AND m2u.MarathonUserID IN (".implode(", ", Connection::GetSQLArray($marathonUserIDs)).")
				  GROUP BY m2u.MarathonUserID";
	    return $stmt->FetchIndexedList($query);
	}
}