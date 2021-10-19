<?php

class MarathonStage extends LocalObject 
{	
	private $module;

	public function __construct($module)
	{
		parent::LocalObject();
		$this->module = $module;
	}
	
	public function loadForUser($marathonUserID, $customPartID = null)
	{
	    $query = "SELECT s.StageID, s.SortOrder AS StageNumber, p.PartID, p.Title AS PartTitle, p.Type AS PartType, p.XP AS PartXP, p.Description AS PartDescription,
                COUNT(pt.TaskID) AS TaskCount, pm.StepID
            FROM `marathon_stage` s
            LEFT JOIN `marathon_stage_part` p ON s.StageID=p.StageID
            LEFT JOIN `marathon_stage_part_task` pt ON p.PartID=pt.PartID
            LEFT JOIN `marathon_stage_part_map` pm ON p.PartID=pm.PartID
            LEFT JOIN `marathon_stage_part2user` p2u ON p.PartID=p2u.PartID
            WHERE p2u.MarathonUserID=".intval($marathonUserID).($customPartID?" AND p2u.PartID=".intval($customPartID):"")."
            GROUP BY p.PartID
            ORDER BY s.SortOrder DESC, p.SortOrder DESC LIMIT 1";
	    $this->LoadFromSQL($query);
	    $this->SetProperty("TaskCountText", counting($this->GetIntProperty('TaskCount'), GetTranslation('taskcountsuffixes', $this->module)));
	    
	    //init user stage and part if not init yet
	    if(!$this->IsPropertySet("PartID")){
	        if($customPartID){
	            return false;
	        }
	        elseif($this->initUserStage($marathonUserID)){
	            $this->loadForUser($marathonUserID);
	        }
	    }
	    
	    return true;
	}
	
	public function loadPartList($marathonUserID)
	{
	    $stmt = GetStatement();
	    $query = "SELECT p.PartID, p.Title, p.Type, p2u.SolutionID, p2u.Status, CASE p.PartID  WHEN ".$this->GetIntProperty("PartID")." THEN 1 ELSE 0 END as Current
            FROM `marathon_stage_part` p
            LEFT JOIN `marathon_stage_part2user` p2u ON p.PartID=p2u.PartID AND p2u.MarathonUserID=".intval($marathonUserID)."
            WHERE p.StageID=".$this->GetIntProperty("StageID")." AND (p2u.MarathonUserID IS NULL OR p2u.MarathonUserID=".intval($marathonUserID).")
            ORDER BY p.SortOrder";
	    return $stmt->FetchList($query);
	}
	
	public function loadListForUser($marathonUserID, $CustomPartID = null)
	{
	    $stmt = GetStatement();
	    $query = "SELECT s.StageID, s.Title AS StageTitle, s.SortOrder AS StageNumber, p.PartID, p.Title, p.Type, p.XP, p.Description,
                COUNT(pt.TaskID) AS TaskCount, pm.StepID, p2u.SolutionID, p2u.Status
            FROM `marathon_stage` s
            LEFT JOIN `marathon_stage_part` p ON s.StageID=p.StageID
            LEFT JOIN `marathon_stage_part_task` pt ON p.PartID=pt.PartID
            LEFT JOIN `marathon_stage_part_map` pm ON p.PartID=pm.PartID
            LEFT JOIN `marathon_stage_part2user` p2u ON p.PartID=p2u.PartID AND p2u.MarathonUserID=".intval($marathonUserID)."
            GROUP BY p.PartID
            ORDER BY s.SortOrder, p.SortOrder";
	    
	    $partList = $stmt->FetchList($query);
	    $stageList = array();
	    $currentStageIndex = -1;
	    for($i=0; $i<count($partList); $i++){
	        if($currentStageIndex == -1 || $stageList[$currentStageIndex]['StageID'] != $partList[$i]['StageID']){
	            $currentStageIndex++;
	            $stageList[$currentStageIndex] = array(
	                'StageID' => $partList[$i]['StageID'],
	                'StageTitle' => $partList[$i]['StageTitle'],
	                'StageNumber' => $partList[$i]['StageNumber'],
	                'PartList' => array()
	            );
	            if($partList[$i]['Status']){
	                $stageList[$currentStageIndex]['Opened'] = 1;
	            }
	            $stageList[$currentStageIndex]['PartID'] = $partList[$i]['PartID'];
	            $stageList[$currentStageIndex]['PartTitle'] = $partList[$i]['Title'];
	            $stageList[$currentStageIndex]['PartType'] = $partList[$i]['Type'];
	            $stageList[$currentStageIndex]['PartXP'] = $partList[$i]['XP'];
	            $stageList[$currentStageIndex]['PartDescription'] = $partList[$i]['Description'];
	            $stageList[$currentStageIndex]['TaskCount'] = $partList[$i]['TaskCount'];
	            $stageList[$currentStageIndex]['TaskCountText'] = counting(intval($partList[$i]['TaskCount']), GetTranslation('taskcountsuffixes', $this->module));
	        }
	        if($CustomPartID == $partList[$i]['PartID'] || $partList[$i]['Status'] == 'available' && $CustomPartID == null){
	            $stageList[$currentStageIndex]['Current'] = 1;
	            $stageList[$currentStageIndex]['PartID'] = $partList[$i]['PartID'];
	            $stageList[$currentStageIndex]['PartTitle'] = $partList[$i]['Title'];
	            $stageList[$currentStageIndex]['PartType'] = $partList[$i]['Type'];
	            $stageList[$currentStageIndex]['PartXP'] = $partList[$i]['XP'];
	            $stageList[$currentStageIndex]['PartDescription'] = $partList[$i]['Description'];
	            $stageList[$currentStageIndex]['TaskCount'] = $partList[$i]['TaskCount'];
	            $stageList[$currentStageIndex]['TaskCountText'] = counting(intval($partList[$i]['TaskCount']), GetTranslation('taskcountsuffixes', $this->module));
				//map id
				if ( !empty($partList[$i]['StepID']) ){
					$stageList[$currentStageIndex]['StepID'] = $partList[$i]['StepID'];
				}
				//current part
				$partList[$i]['Current'] = 1;
	        }

	        //add part in stage
			$stageList[$currentStageIndex]['PartList'][] = $partList[$i];

	    }
	    
	    return $stageList;
	}
	
	public function loadCompleteStage($stageID, $marathonUserID)
	{
	    $stmt = GetStatement();
	    $query = "SELECT s.StageID, s.SortOrder, p.PartID, p.Type, t.TaskID
            FROM `marathon_stage` s
            LEFT JOIN `marathon_stage_part` p ON s.StageID=p.StageID
            LEFT JOIN `marathon_stage_part_task` t ON p.PartID=t.PartID
            WHERE s.StageID=".intval($stageID);
	    $partList = $stmt->FetchList($query);
	    
	    $totalCount = 0;
	    $taskIDs = array();
	    $partIDs = array();
	    for($i=0; $i<count($partList); $i++){
	        //if($partList[$i]['Type'] != 'map'){
	            $totalCount++;
	            if($partList[$i]['Type'] == 'tasks'){
	                $taskIDs[] = $partList[$i]['TaskID'];
	            }
	            else {
	                $partIDs[] = $partList[$i]['PartID'];
	            }
	        //}
	    }
	    
	    $completedPartCount = 0;
	    if(count($partIDs) > 0){
	        $completedPartCount = $stmt->FetchField("SELECT COUNT(SolutionID) FROM `marathon_stage_part2user` WHERE MarathonUserID=".intval($marathonUserID)." AND PartID IN (".implode(", ", $partIDs).") AND Status='complete'");
	    }
	    $completedTaskCount = 0;
	    if(count($taskIDs) > 0){
	        $completedTaskCount = $stmt->FetchField("SELECT COUNT(TaskSolutionID) FROM `marathon_stage_part_task2user` WHERE MarathonUserID=".intval($marathonUserID)." AND TaskID IN (".implode(", ", $taskIDs).") AND Status='complete'");
	    }
	    $completeCount = $completedPartCount + $completedTaskCount;
	    
	    if($totalCount > 0 && $completeCount > 0){
	        $this->SetProperty("NextStage", intval($partList[0]['SortOrder']) + 1);
	        $this->SetProperty("CompletePercent", intval($completeCount * 100.0 / $totalCount));
	        return true; 
	    }
	    return false;
	}
	
	public function getUserStat($nextStageNum){
	    $stmt = GetStatement();
	    $totalUserCount = $stmt->FetchField("SELECT COUNT(MarathonUserID) FROM `marathon_user`");

		$query = "SELECT COUNT(p2u.MarathonUserID) 
            FROM `marathon_stage_part2user` p2u
            LEFT JOIN `marathon_stage_part` p ON p2u.PartID=p.PartID 
            LEFT JOIN `marathon_stage` s ON p.StageID=s.StageID 
            WHERE s.SortOrder=".intval($nextStageNum);

	    /*$query = "SELECT COUNT(p2u.MarathonUserID)
            FROM `marathon_stage_part2user` p2u
            LEFT JOIN `marathon_stage_part` p ON p2u.PartID=p.PartID 
            LEFT JOIN `marathon_stage` s ON p.StageID=s.StageID 
            WHERE s.SortOrder=".intval($nextStageNum)."
            GROUP BY p2u.MarathonUserID";*/
	    $otherUserCount = $stmt->FetchField($query);

		$result = $totalUserCount - $otherUserCount;

	    return array(
	        "PeopleCount" => ($result > 0) ? $result : 0
	    );
	}
	
	private function initUserStage($marathonUserID)
	{
	    $stmt = GetStatement();
	    $query = "SELECT p.PartID
            FROM `marathon_stage` s
            LEFT JOIN `marathon_stage_part` p ON s.StageID=p.StageID
            ORDER BY s.SortOrder, p.SortOrder LIMIT 1";
	    $minPartID = $stmt->FetchField($query);
	    if($minPartID){
	        $query = "INSERT INTO `marathon_stage_part2user` SET
                PartID=".intval($minPartID).",
                MarathonUserID=".intval($marathonUserID);
	        if($stmt->Execute($query)) {
	            return true;
	        }
	    }
	    return false;
	}


	//Static getters
	public static function getLastStageID(){
		$stmt = GetStatement();
		$query = "SELECT StageID FROM marathon_stage ORDER BY SortOrder DESC LIMIT 1";
		return $stmt->FetchField($query);
	}

	public static function getLastPartID(){
		$stmt = GetStatement();
		$query = "SELECT PartID FROM marathon_stage_part WHERE StageID = " . self::getLastStageID() . " ORDER BY SortOrder DESC LIMIT 1";
		return $stmt->FetchField($query);
	}

	public static function getCountStages(){
		$stmt = GetStatement();
		$query = "SELECT COUNT(StageID) FROM marathon_stage";
		return $stmt->FetchField($query);
	}

	public static function getCountPartsToComplete($sortOrder){
		$stmt = GetStatement();
		$query = "SELECT COUNT(part.PartID) FROM marathon_stage_part AS part
				  LEFT JOIN marathon_stage AS stage ON part.StageID = stage.StageID
				  WHERE stage.SortOrder <= " . intval($sortOrder);
		return $stmt->FetchField($query);
	}
}

?>
