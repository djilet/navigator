<?php

class ProftestTask extends LocalObject 
{	
	private $module;
	protected $list;

	public function __construct($module = 'proftest')
	{
		parent::LocalObject();
		$this->module = $module;
		$this->list = new LocalObjectList();
	}
	
	public function loadForUser($proftest, $proftestUserID, $skipTaskSolutionID=null, $directTaskID=null)
	{
	    $stmt = GetStatement();
	    $query = "SELECT t.TaskID, t2u.TaskSolutionID, t2u.Status
            FROM `proftest_task` t 
            LEFT JOIN `proftest_task2user` t2u ON t.TaskID=t2u.TaskID AND t2u.ProftestUserID=".intval($proftestUserID)."
            WHERE t.ProftestID=".$proftest->GetIntProperty('ProftestID')." 
            ORDER BY t.SortOrder, t.TaskID";
	    $taskList = $stmt->FetchList($query);
	    if(count($taskList) > 0){
	        $taskID = null;
	        $prevTaskID = null;
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
                        if($i > 0){
                            $prevTaskID = $taskList[$i-1]['TaskID'];
                        }
                        $taskList[$i]['Current'] = 1;
                        if(!$taskList[$i]['Status']){
                            $query = "INSERT INTO `proftest_task2user` SET
                                TaskID=".$taskID.",
                                ProftestUserID=".intval($proftestUserID);
                            $stmt->Execute($query);
                        }
                    }
                }
                elseif($taskList[$i]['Status'] != 'complete'){
                    if($ignoreSkip && $taskID == null) {
                        $taskID = $taskList[$i]['TaskID'];
                        if($i > 0){
                            $prevTaskID = $taskList[$i-1]['TaskID'];
                        }
                        $taskList[$i]['Current'] = 1;
                        if(!$taskList[$i]['Status']){
                            $query = "INSERT INTO `proftest_task2user` SET
                                TaskID=".$taskID.",
                                ProftestUserID=".intval($proftestUserID);
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
	        if(!$taskID && $firstAvailableInd > -1){
	            $taskID = $taskList[$firstAvailableInd]['TaskID'];
	            if($firstAvailableInd > 0){
	                $prevTaskID = $taskList[$firstAvailableInd-1]['TaskID'];
	            }
	            $taskList[$firstAvailableInd]['Current'] = 1;
	        }
	        
	        if(!$taskID){
	            $taskID = $taskList[0]['TaskID'];
	            $taskList[0]['Current'] = 1;
	            $partComplete = true;
	        }
	        
	        if($taskID){
	            $query = "SELECT t.TaskID, t.Type AS TaskType, t.Prefix, t.Text, t.AnswerCount, t2u.TaskSolutionID
                    FROM `proftest_task` t
                    LEFT JOIN `proftest_task2user` t2u ON t.TaskID=t2u.TaskID AND t2u.ProftestUserID=".intval($proftestUserID)."
                    WHERE t.TaskID=".intval($taskID);
	            $this->LoadFromSQL($query);
	        }
	        
	        if($partComplete){
	            $this->SetProperty("ProftestComplete", 1);
	        }
	        if($prevTaskID){
	            $this->SetProperty("PrevTaskID", $prevTaskID);
	        }
	        
	        $this->SetProperty("TaskList", $taskList);
	        
	        $query = "SELECT a.AnswerID, a.Title, (a2u.AnswerID IS NOT NULL) AS Selected
                FROM proftest_answer a
                LEFT JOIN proftest_answer2user a2u ON a.AnswerID=a2u.AnswerID AND a2u.ProftestUserID=".intval($proftestUserID)."
                WHERE a.TaskID=".intval($taskID)."
                ORDER BY a.SortOrder";
	        $this->SetProperty("AnswerList", $stmt->FetchList($query));
	        
	        return true;
	    }
	    return false;
	}

	public function loadByID($id){
		$query = "SELECT * FROM proftest_task WHERE TaskID = " . intval($id);
		$this->LoadFromSQL($query);
	}

	public function completeTask($taskID, $proftestUserID, $answer)
	{
	    $stmt = GetStatement();
	    $query = "SELECT t2u.TaskSolutionID, t.Type
            FROM `proftest_task2user` t2u 
            LEFT JOIN `proftest_task` t ON t2u.TaskID=t.TaskID
            WHERE t2u.TaskID=".intval($taskID)." AND t2u.ProftestUserID=".intval($proftestUserID);
	    $taskInfo = $stmt->FetchRow($query);
	    if($taskInfo){
	        if (!isset($answer) || count($answer) == 0)
	        {
	            $this->AddError("task-empty-answer-error", $this->module);
	            return false;
	        }
	        $query = "UPDATE `proftest_task2user` SET
                Status='complete',
                Completed=".Connection::GetSQLString(GetCurrentDateTime())."
                WHERE TaskSolutionID=".$taskInfo['TaskSolutionID'];
	        $stmt->Execute($query);
	        $stmt->Execute("DELETE FROM proftest_answer2user WHERE ProftestUserID=".intval($proftestUserID)." AND TaskID=".intval($taskID));
	        for($i=0; $i<count($answer); $i++){
	            $query = "INSERT INTO `proftest_answer2user` SET
                    ProftestUserID=".intval($proftestUserID).",
                    TaskID=".intval($taskID).",
                    AnswerID=".intval($answer[$i]);
	            $stmt->Execute($query);
            }
	    }
	    return $taskInfo;
	}

	public function save(LocalObject $request){
		$stmt = GetStatement();

		if (!$this->validate($request)){
			return false;
		}

		if ($request->GetIntProperty("TaskID") > 0){
			$op = "UPDATE `proftest_task` SET";
			$where =  " WHERE TaskID = " . $request->GetIntProperty("TaskID");
		}
		else{
			$op = "INSERT INTO `proftest_task` SET ";
			$where = '';
		}

		$query = $op . "
			`ProftestID` = " . $request->GetPropertyForSQL("ProftestID") . ",
			`Prefix` = " . $request->GetPropertyForSQL("Prefix") . ",
			`Text` = " . $request->GetPropertyForSQL("Text") . ",
			`SortOrder` = " . $request->GetPropertyForSQL("SortOrder") .
			$where;

		if (!$stmt->Execute($query)){
			$this->AddError('task-save-error', $this->module);
			return false;
		}
		$this->saveCategory($request->GetIntProperty("TaskID"), $request->GetProperty('Category'));
		return true;
	}

	public function saveCategory($taskID, $IDs){
		if (!is_array($IDs)){
			return false;
		}
		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM proftest_task2category WHERE TaskID = " . intval($taskID));

		foreach ($IDs as $index => $ID) {
			$query = "INSERT INTO proftest_task2category SET TaskID = " . intval($taskID) . ", CategoryID = " . intval($ID);
			$stmt->Execute($query);
		}
	}

	public function validate(LocalObject $request){
		$this->ClearErrors();
		if(empty($request->GetProperty('Prefix'))){
			$this->AddError('empty-prefix', $this->module);
		}
		if(empty($request->GetProperty('Text'))){
			$this->AddError('empty-text', $this->module);
		}
		if (!is_numeric($request->GetProperty('SortOrder'))){
			$this->AddError('sort-order-not-numeric', $this->module);
		}
		if (empty($request->GetProperty('Category')[0])){
			$this->AddError('empty-category', $this->module);
		}

		if ($this->HasErrors()){
			return false;
		}
		return true;
	}

	public function remove($id){
		$stmt = GetStatement();

		$query = "DELETE FROM proftest_task WHERE TaskID = " . intval($id);
		$stmt->Execute($query);
	}

//List
	public function getObjectList(){
		return $this->list;
	}

	public function loadList($proftest){
		$this->list->LoadFromSQL("SELECT TaskID, ProftestID, Text, SortOrder FROM proftest_task WHERE ProftestID = " . intval($proftest));
	}
}

?>