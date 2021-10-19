<?php
require_once(dirname(__FILE__)."/../include/init.php");

$delimiter = ',';
$enclosure = '"';

$file = fopen('source/marathon.csv', 'r');
$stmt = GetStatement();

$stmt->Execute('DELETE FROM marathon_stage_part_map');
$stmt->Execute('DELETE FROM marathon_stage_part_task');
$stmt->Execute('DELETE FROM marathon_stage_part_video');
$stmt->Execute('DELETE FROM marathon_stage_part_webinar');
$stmt->Execute('DELETE FROM marathon_stage_part');
$stmt->Execute('DELETE FROM marathon_stage');

$stageSortOrder = 1;
$partSortOrder = 1;
$taskSortOrder = 1;

while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
    if ($data[0] == 'id этапа') {
        continue;
    }
    
    //create stage
    $stageID = intval($data[0]);
    if(!$stmt->FetchField('SELECT COUNT(*) FROM marathon_stage WHERE StageID='.$stageID)) {
        $query = "INSERT INTO marathon_stage SET 
            StageID=".$stageID.",
            Title=".Connection::GetSQLString($data[1]).",
            SortOrder=".$stageSortOrder;
        $stmt->Execute($query);
        $stageSortOrder ++;
        $partSortOrder = 1;
    }
    
    //create part
    $partID = $stageID*100 + intval($data[2]);
    $type='';
    $taskType='';
    if($data[7] == 'Развернутый ответ'){
        $type = 'tasks';
        $taskType = 'extended';
    }
    elseif($data[7] == 'Теория'){
        $type = 'tasks';
        $taskType = 'theory';
    }
    elseif(strlen($data[11]) > 0){
        $type = 'webinar';
    }
    elseif(strlen($data[12]) > 0){
        $type = 'video';
    }
    elseif(strlen($data[13]) > 0){
        $type = 'map';
    }
    if(!$stmt->FetchField('SELECT COUNT(*) FROM marathon_stage_part WHERE PartID='.$partID)) {
        $query = "INSERT INTO marathon_stage_part SET
            PartID=".$partID.",
            StageID=".$stageID.",
            Title=".Connection::GetSQLString($data[3]).",
            Type=".Connection::GetSQLString($type).",
            XP=".intval($data[5]).",
            Description=".Connection::GetSQLString($data[4]).",
            SortOrder=".$partSortOrder;
        if(strlen($data[10])>0){
            $query .= ", ActiveDateTime=".Connection::GetSQLString(DateTime::createFromFormat('d.m.Y H:i', $data[10])->format('Y-m-d H:i:s'));
        }
        if(strlen($data[6])>0){
            $query .= ", MinCountForComplete=".intval($data[6]);
        }
        $stmt->Execute($query);
        $partSortOrder ++;
        $taskSortOrder = 1;
    }
    else {
        $query = "UPDATE marathon_stage_part SET
            XP=XP+".intval($data[5])."
            WHERE PartID=".$partID;
        $stmt->Execute($query);
    }
    
    //custom actions for types
    if($type == 'webinar'){
        $url = $data[11];
        if(substr($url, 0, 4 ) !== 'http'){
            $url = 'http://'.$url;
        }
        $query = "INSERT INTO marathon_stage_part_webinar SET
            PartID=".$partID.",
            URL=".Connection::GetSQLString($url);
        $stmt->Execute($query);
    }
    elseif($type == 'video'){
        $url = $data[12];
        $ind = strrpos($url, '/');
        $youtubeID = substr($url, $ind+1);
        $query = "INSERT INTO marathon_stage_part_video SET
            PartID=".$partID.",
            YoutubeID=".Connection::GetSQLString($youtubeID);
        $stmt->Execute($query);
    }
    elseif($type == 'tasks'){
        $taskID = $partID*100 + $taskSortOrder;
        $query = "INSERT INTO marathon_stage_part_task SET
            TaskID=".$taskID.",
            PartID=".$partID.",
            Type=".Connection::GetSQLString($taskType).",
            Title=".Connection::GetSQLString($data[8]).",
            Description=".Connection::GetSQLString($data[9]).",
            XP=".intval($data[5]).",
            SortOrder=".$taskSortOrder;
        $stmt->Execute($query);
        $taskSortOrder ++;
    }
    elseif($type == 'map'){
        $query = "SELECT StepID FROM marathon_map WHERE Title=".Connection::GetSQLString($data[13]);
        $stepID = $stmt->FetchField($query);
        if($stepID){
            $query = "INSERT INTO marathon_stage_part_map SET
                PartID=".$partID.",
                StepID=".$stepID;
            $stmt->Execute($query);
            
            $query = "UPDATE marathon_map SET
                SubTitle=".Connection::GetSQLString($data[14]).",
                XP=".intval($data[15]).",
                Description=".Connection::GetSQLString($data[16]).",
                PDFComment=".Connection::GetSQLString($data[17])."
                WHERE StepID=".$stepID;
            $stmt->Execute($query);
        }
        else {
            print_r('Ошибка поиска этапа карты: '.$query.'<br/>');
        }
    }
}

fclose($file);

?>