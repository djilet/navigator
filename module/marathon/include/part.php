<?php

class MarathonPart extends LocalObject 
{	
	private $module;

	public function __construct($module)
	{
		parent::LocalObject();
		$this->module = $module;
	}
	
	public function loadByID($partID, $marathonUserID)
	{
	    $query = "SELECT p.PartID, p.Title, p.Type, p.XP, p.MinCountForComplete, pw.URL AS WebinarURL, pv.YoutubeID, p2u.Status
            FROM `marathon_stage_part` p
            LEFT JOIN `marathon_stage_part_webinar` pw ON p.PartID=pw.PartID
            LEFT JOIN `marathon_stage_part_video` pv ON p.PartID=pv.PartID
            LEFT JOIN `marathon_stage_part2user` p2u ON p.PartID=p2u.PartID AND p2u.MarathonUserID=".intval($marathonUserID)."
            WHERE p.PartID=".intval($partID)." AND p2u.MarathonUserID=".intval($marathonUserID);
	    $this->LoadFromSQL($query);
	}
	
	public function completePart($partID, $marathonUserID)
	{
	    $result = array();
	    $stmt = GetStatement();
	    $query = "SELECT p2u.SolutionID, p2u.Status, p.SortOrder AS PartSortOrder, s.StageID, s.SortOrder AS StageSortOrder, p.XP
            FROM `marathon_stage_part2user` p2u 
            LEFT JOIN `marathon_stage_part` p ON p2u.PartID=p.PartID
            LEFT JOIN `marathon_stage` s ON p.StageID=s.StageID
            WHERE p2u.PartID=".intval($partID)." AND p2u.MarathonUserID=".intval($marathonUserID);
	    $solutionInfo = $stmt->FetchRow($query);
	    if($solutionInfo){
	        $stmt->Execute("UPDATE `marathon_stage_part2user` SET XP=".$solutionInfo['XP'].",Status='complete',Completed=".Connection::GetSQLString(GetCurrentDateTime())." WHERE SolutionID=".$solutionInfo['SolutionID']);
	        
	        if($solutionInfo['Status'] == 'available'){
	            //add next part to user if necessary
	            $query = "SELECT p.PartID, p.Type, pm.StepID
                FROM `marathon_stage_part` p
                LEFT JOIN `marathon_stage_part_map` pm ON p.PartID=pm.PartID
                WHERE p.StageID=".$solutionInfo['StageID']." AND p.SortOrder>".$solutionInfo['PartSortOrder']."
                ORDER BY p.SortOrder LIMIT 1";
	            $newPartInfo = $stmt->FetchRow($query);
	            if(!$newPartInfo){
	                //switch to next stage
	                $result['NextStage'] = 1;
	                $result['StageID'] = $solutionInfo['StageID'];
	                $query = "SELECT p.PartID, p.Type, pm.StepID
                    FROM `marathon_stage_part` p
                    LEFT JOIN `marathon_stage_part_map` pm ON p.PartID=pm.PartID
                    LEFT JOIN `marathon_stage` s ON p.StageID=s.StageID
                    WHERE s.SortOrder>".$solutionInfo['StageSortOrder']." ORDER BY s.SortOrder, p.SortOrder LIMIT 1";
	                $newPartInfo = $stmt->FetchRow($query);
	            }
	            if($newPartInfo){
	                $query = "SELECT p2u.SolutionID
                    FROM `marathon_stage_part2user` p2u
                    WHERE p2u.PartID=".$newPartInfo['PartID']." AND p2u.MarathonUserID=".intval($marathonUserID);
	                if(!$stmt->FetchField($query)){
	                    $query = "INSERT INTO `marathon_stage_part2user` SET
                        PartID=".$newPartInfo['PartID'].",
                        MarathonUserID=".intval($marathonUserID).",
                        XP=0";
	                    $stmt->Execute($query);
	                    
	                    //additional actions
	                    if($newPartInfo['Type'] == 'map'){
	                        //set available map step for current user
	                        $mapStep = new MarathonMapStep($newPartInfo['StepID'], intval($marathonUserID));
	                        $mapStep->addStepForUser();
	                    }
	                }
	            }
	        }
	    }
	    return $result;
	}
	
	public function findByMapStep($stepID, $marathonUserID){
	    $stmt = GetStatement();
	    $query = "SELECT p.PartID
            FROM `marathon_stage_part` p
            LEFT JOIN `marathon_stage_part2user` p2u ON p2u.PartID=p.PartID
            LEFT JOIN `marathon_stage_part_map` pm ON p.PartID=pm.PartID
            WHERE pm.StepID=".intval($stepID)." AND p2u.MarathonUserID=".intval($marathonUserID)." AND p2u.Status='available' LIMIT 1";
	    return $stmt->FetchField($query);
	}
}

?>
