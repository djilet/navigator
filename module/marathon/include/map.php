<?php
require_once(dirname(__FILE__)."/../init.php");
require_once(dirname(__FILE__)."/map_step.php");

class MarathonMap extends LocalObject{
    private $module;
    private $stmt;
    private $user_map;
    private $maraphon_user_id;
    private $maraphon_user_xp;

    public function __construct($maraphon_user_id, $maraphon_user_xp){
        parent::LocalObject();
        $this->stmt = GetStatement();

        $this->maraphon_user_id = $maraphon_user_id;
        $this->maraphon_user_xp = $maraphon_user_xp;
        $this->loadMap4User();
    }

    //Get
	public function getMapAndAnswers(){
		$result = array();
		foreach ($this->user_map as $key => $value) {
			$result['Content'][$key] = $value;
			$map = new MarathonMapStep($value['StepID'], $this->maraphon_user_id);
			if (!empty($map->GetProperty('Content'))){
				foreach ($map->GetProperty('Content') as $index => $item) {
					$selected = (isset($item['Selected']) ? $item['Selected'] : 0);
					if (!empty($item['List'])){
						$result['Content'][$key]['AnswersList'][$index]['ListTitle'] = $item['ListTitle'];
						foreach ($item['List'] as $list_key => $list_val) {
							//print_r($list_val);
							if ($list_val['Selected'] > 0){
								$result['Content'][$key]['AnswersList'][$index]['Answers'][]['Name'] = $list_val['Title'];
							}
						}
					}
					elseif (!empty($item['SpecialityList']) && $selected > 0){
						$result['Content'][$key]['AnswersList'][$index]['ListTitle'] = $item['Title'];
						foreach ($item['SpecialityList'] as $spec_key => $speciality) {
							$result['Content'][$key]['AnswersList'][$index]['Answers'][$spec_key]['Name'] = $speciality['Title'];
							//TODO init other years
							if (!empty($speciality['BudgetNext'])){
								$result['Content'][$key]['AnswersList'][$index]['Answers'][$spec_key]['BudgetNext'] = $speciality['BudgetNext'];
							}
							elseif($speciality['BudgetText']){
								$result['Content'][$key]['AnswersList'][$index]['Answers'][$spec_key]['BudgetText'] = $speciality['BudgetText'];
							}
						}
					}
					elseif ($selected > 0){
						//$this->user_map[$key]['Answers'][$index]['Name'] = $item['Title'];
						$result['Content'][$key]['Answers'][$index]['Name'] = $item['Title'];
					}
				}
			}
		}
		return $result;
	}
    public function getMap(){
        return $this->user_map;
    }
    public function getMapBar(){
        $next_step = $this->getNextStep();
        $max_xp = $this->getMaxStep()['XP'];

        $bar['NextStepName'] = $next_step['Name'];
        $bar['NeedXP'] = $next_step['XP'] - $this->maraphon_user_xp;
        $bar['Status'] = intval((100 / $max_xp) * $this->maraphon_user_xp);

        /*step status
        $bar['Status'] = intval((100 / ($next_step['XP'] - $max_open_step['XP'])) * ($this->maraphon_user_xp - $max_open_step['XP']));*/

        return $bar;
    }

    //Load

    /**
     * Load map for user
     * @return bool
     */
    private function loadMap4User(){
        $query = "SELECT DISTINCT m.StepID, m.Name, m.Title, m.SubTitle, m.PDFComment, m.XP, m2u.Status,
        CONCAT(" . Connection::GetSQLString(MARATHON_IMAGE_URL_PREFIX . 'map/') . ", Icon) AS ImagePath, s.SortOrder AS StageNumber
        FROM marathon_map as m
        LEFT JOIN marathon_map2user as m2u ON " . $this->maraphon_user_id . " = m2u.MarathonUserID AND m.StepID = m2u.StepID
        LEFT JOIN marathon_stage_part_map as spm ON spm.StepID=m.StepID
        LEFT JOIN marathon_stage_part as sp ON sp.PartID=spm.PartID
        LEFT JOIN marathon_stage as s ON s.StageID=sp.StageID
        GROUP BY m.StepID
        ORDER BY m.SortOrder ASC";
        if($map = $this->stmt->FetchList($query)){
			$this->user_map = $map;
            return true;
        }
    }


    //Steps
    public function getNextStep(){
        $query = "SELECT StepID, Name, XP FROM marathon_map WHERE SortOrder > " . $this->maraphon_user_xp;
        if($next_step = $this->stmt->FetchRow($query)){
           return $next_step;
       }
    }
    public function getMaxStep(){
        $query = "SELECT StepID, Name, XP FROM marathon_map WHERE XP = (SELECT MAX(XP) FROM marathon_map)";
        if($max_step = $this->stmt->FetchRow($query)){
            return $max_step;
        }
    }
    public function getMaxOpenStep(){
        $query = "SELECT StepID, Name, XP FROM marathon_map WHERE XP = (SELECT MAX(XP) FROM marathon_map WHERE XP <= " . $this->maraphon_user_xp . ")";
        if($max_open_step = $this->stmt->FetchRow($query)){
           return $max_open_step;
       }
    }
	public static function getLastCompleted($maraphon_user_id){
		$stmt = GetStatement();
		$query = "SELECT * FROM marathon_map WHERE StepID IN (
			  	  SELECT StepID FROM marathon_map2user WHERE MarathonUserID = " . $maraphon_user_id . " AND Status = 'completed')
				  ORDER BY SortOrder DESC LIMIT 1";
		if($last_completed = $stmt->FetchRow($query)){
			return $last_completed;
		}
	}
	public static function getUserXP($maraphon_user_id){
		$stmt = GetStatement();
		$query = "SELECT XP FROM marathon_user WHERE MarathonUserID = $maraphon_user_id";
		return $stmt->FetchField($query);
	}


	//Static
	public static function GetStepsCount(){
		return GetStatement()->FetchField("SELECT COUNT(StepID) FROM marathon_map");
	}
}

?>
