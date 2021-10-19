<?php

class MarathonTask extends LocalObject 
{	
	private $module;

	public function __construct($module)
	{
		parent::LocalObject();
		$this->module = $module;
	}
	
	public function loadForUser($part, $marathonUserID, $skipTaskSolutionID=null, $directTaskID=null)
	{
	    $stmt = GetStatement();
	    $query = "SELECT t.TaskID, t2u.TaskSolutionID, t2u.Status
            FROM `marathon_stage_part_task` t 
            LEFT JOIN `marathon_stage_part_task2user` t2u ON t.TaskID=t2u.TaskID AND t2u.MarathonUserID=".intval($marathonUserID)."
            WHERE t.PartID=".$part->GetIntProperty('PartID')." 
            ORDER BY t.SortOrder";
	    $taskList = $stmt->FetchList($query);
	    if(count($taskList) > 0){
	        $taskID = null;
	        $firstAvailableInd = -1;
	        $ignoreSkip = true;
	        if($skipTaskSolutionID) $ignoreSkip=false;
	        for($i=0; $i<count($taskList); $i++){
	            if($taskList[$i]['Status'] == 'complete'){
	                $taskList[$i]['Complete'] = 1;
	            }
	            
                if($directTaskID){
                    if($directTaskID == $taskList[$i]['TaskID']){
                        $taskID = $taskList[$i]['TaskID'];
                        $taskList[$i]['Current'] = 1;
                        if(!$taskList[$i]['Status']){
                            $query = "INSERT INTO `marathon_stage_part_task2user` SET
                            TaskID=".$taskID.",
                            MarathonUserID=".intval($marathonUserID);
                            $stmt->Execute($query);
                        }
                    }
                }
                elseif($taskList[$i]['Status'] != 'complete'){
                    if($ignoreSkip && $taskID == null) {
                        $taskID = $taskList[$i]['TaskID'];
                        $taskList[$i]['Current'] = 1;
                        if(!$taskList[$i]['Status']){
                            $query = "INSERT INTO `marathon_stage_part_task2user` SET
                        TaskID=".$taskID.",
                        MarathonUserID=".intval($marathonUserID);
                            $stmt->Execute($query);
                        }
                    }
                    if($firstAvailableInd == -1){
                        $firstAvailableInd = $i;
                    }
                }
                
                if($taskList[$i]['TaskSolutionID'] == $skipTaskSolutionID){
                    $ignoreSkip = true;
                }
	        }
	        
	        $partComplete = false;
	        $partLastTask = false;
	        if($part->GetProperty('MinCountForComplete')){
	            $completeCount = $stmt->FetchField("SELECT count(s.TaskSolutionID)
                        FROM marathon_stage_part_task2user s
                        LEFT JOIN marathon_stage_part_task t ON s.TaskID=t.TaskID
                        WHERE s.Status='complete' AND s.MarathonUserID=".intval($marathonUserID)." AND t.PartID=".$part->GetIntProperty('PartID'));
	            if($completeCount >= $part->GetIntProperty('MinCountForComplete')){
	                $partComplete = true;
	            }
	        }
	        
	        if(!$taskID && $firstAvailableInd > -1){
	            $taskID = $taskList[$firstAvailableInd]['TaskID'];
	            $taskList[$firstAvailableInd]['Current'] = 1;
	            $partLastTask = true;
	        }
	        
	        if(!$taskID){
	            $taskID = $taskList[0]['TaskID'];
	            $taskList[0]['Current'] = 1;
	            $partComplete = true;
	            $partLastTask = true;
	        }
	        
	        if($taskID){
	            $query = "SELECT t.TaskID, t.Type AS TaskType, t.Title AS TaskTitle, t.Description AS TaskDescription, t.XP AS TaskXP, t2u.TaskSolutionID, t2u.Answer AS TaskAnswer
                    FROM `marathon_stage_part_task` t
                    LEFT JOIN `marathon_stage_part_task2user` t2u ON t.TaskID=t2u.TaskID AND t2u.MarathonUserID=".intval($marathonUserID)."
                    WHERE t.TaskID=".intval($taskID);
	            $this->LoadFromSQL($query);
	        }
	        
	        if($partComplete){
	            $this->SetProperty("PartComplete", 1);
	        }
	        if($partLastTask){
	            $this->SetProperty("PartLastTask", 1);
	        }
	        
	        $this->SetProperty("TaskList", $taskList);
	        return true;
	    }
	    return false;
	}
	
	public function completeTask($taskID, $marathonUserID, $answer)
	{
	    $stmt = GetStatement();
	    $query = "SELECT t2u.TaskSolutionID, t.Type, t.XP
            FROM `marathon_stage_part_task2user` t2u 
            LEFT JOIN `marathon_stage_part_task` t ON t2u.TaskID=t.TaskID
            WHERE t2u.TaskID=".intval($taskID)." AND t2u.MarathonUserID=".intval($marathonUserID);
	    $taskInfo = $stmt->FetchRow($query);
	    if($taskInfo){
	        if ($taskInfo['Type'] == 'extended' && strlen($answer) == 0)
	        {
	            $this->AddError("stage-task-extended-answer-empty", $this->module);
	            return false;
	        }
	        $query = "UPDATE `marathon_stage_part_task2user` SET
                Answer=".Connection::GetSQLString($answer).",
                Status='complete',
                XP=".$taskInfo['XP'].",
                Completed=".Connection::GetSQLString(GetCurrentDateTime())."
                WHERE TaskSolutionID=".$taskInfo['TaskSolutionID'];
	        $stmt->Execute($query);
	    }
	    return $taskInfo;
	}
	
	public function loadUserAnswers($taskID, $marathonUserIDs){
	    $stmt = GetStatement();
	    $query = "SELECT t2u.MarathonUserID, t2u.Answer AS Value
				  FROM `marathon_stage_part_task2user` t2u
                  WHERE t2u.TaskID=".intval($taskID)." AND t2u.Status='complete' AND t2u.MarathonUserID IN (".implode(", ", Connection::GetSQLArray($marathonUserIDs)).")";
	    return $stmt->FetchIndexedList($query);
	}
}

?>