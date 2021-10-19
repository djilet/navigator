<?php
require_once(dirname(__FILE__)."/../include/init.php");

$delimiter = ',';
$enclosure = '"';

$stmt = GetStatement();
$request = array_merge($_GET, $_POST);

if(isset($request['CityID'])){
    $fileName = '/source/exhibition/schedule-'.$request['CityID'].'.csv';
    $file = fopen(__DIR__ . $fileName, 'r');
    if($file){
        $query = 'SELECT r.Title, r.RoomID FROM data_exhibition_room r WHERE r.CityID='.intval($request['CityID']);
        $oldRoomList = $stmt->FetchIndexedList($query);
        foreach($oldRoomList as $title=>$room){
            $query = 'SELECT a.Title, a.ActionID FROM data_exhibition_action a WHERE a.RoomID='.intval($room['RoomID']);
            $oldRoomList[$title]['ActionList'] = $stmt->FetchIndexedList($query);
            $stmt->Execute('DELETE FROM data_exhibition_room WHERE RoomID='.intval($room['RoomID']));
            $stmt->Execute('DELETE FROM data_exhibition_action WHERE RoomID='.intval($room['RoomID']));
        }
        $newRoomList = array();
        while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
            $roomTitle = trim($data[0]);
            if(strlen($roomTitle) == 0 || $roomTitle == 'Зал'){
                continue;
            }
            
            $oldRoomInfo = null;
            if(isset($oldRoomList[$roomTitle])){
                $oldRoomInfo = $oldRoomList[$roomTitle];
            }
            
            $roomID = $newRoomList[$roomTitle];
            if(!$roomID){
                $query = 'INSERT INTO data_exhibition_room SET ';
                if($oldRoomInfo != null){
                    $query .= 'RoomID='.intval($oldRoomInfo['RoomID']).', ';
                }
                $query .= 'CityID='.intval($request['CityID']).',
                    Title='.Connection::GetSQLString($roomTitle);
                if($stmt->Execute($query)){
                    $roomID = $stmt->GetLastInsertID();
                    $newRoomList[$roomTitle] = $roomID;
                    echo 'Загружена аудитория ID='.$roomID.', Title='.$roomTitle.'<br/>';
                }
            }
            
            $actionTitle = trim($data[6]);
            $query = 'INSERT INTO data_exhibition_action SET ';
            if($oldRoomInfo != null && isset($oldRoomInfo['ActionList'][$actionTitle])){
                $query .= 'ActionID='.intval($oldRoomInfo['ActionList'][$actionTitle]['ActionID']).',';
            }
            $type = '';
            if($data[5] == 'ЕГЭ и ОГЭ') {$type='experts';}
            elseif($data[5] == 'Для родителей') {$type='parent';}
            elseif($data[5] == 'Обучение за рубежом') {$type='abroad';}
            elseif($data[5] == 'Профориентация') {$type='prof';}
            elseif($data[5] == 'Лекция от вуза') {$type='univer';}
            elseif($data[5] == 'English') {$type='toefl';}
            $query .= 'RoomID='.intval($roomID).',
                TimeFrom='.Connection::GetSQLString($data[2]).',
                TimeTo='.Connection::GetSQLString($data[3]).',
                Type='.Connection::GetSQLString($type).',
                Title='.Connection::GetSQLString($actionTitle).',
                Name='.Connection::GetSQLString($data[7]).',
                Post='.Connection::GetSQLString($data[8]).',
                Description='.Connection::GetSQLString($data[9]);
            if($stmt->Execute($query)){
                $actionID = $stmt->GetLastInsertID();
                echo '&nbsp;Загружена лекция ID='.$actionID.', Title='.$actionTitle.'<br/>';
            }
        }
        fclose($file);
    }
    else {
        echo 'Отсутствует файл для импорта '.$fileName.'<br/>';
    }
}
else {
    echo 'Отсутствует обязательный параметр CityID'.'<br/>';
}

?>