<?php
trait ExhibitionCityCommon{
    
    public function loadSchedule($cityID)
    {
        $stmt = GetStatement();
        $query = 'SELECT r.RoomID, r.Title AS RoomTitle, a.*
            FROM data_exhibition_action a
            LEFT JOIN data_exhibition_room r ON r.RoomID=a.RoomID
            WHERE r.CityID='.intval($cityID).'
            ORDER BY r.Title, a.TimeFrom';
        $actionList = $stmt->FetchList($query);
        $roomList = array();
        foreach($actionList as $action) {
            if(!isset($roomList[$action['RoomID'] . $action['Date']])) {
                $roomList[$action['RoomID'] . $action['Date']] = array(
                    'Date' => $action['Date'],
                    'RoomID' => $action['RoomID'],
                    'Title' => $action['RoomTitle'],
                    'ActionList' => array()
                );
            }
            $roomList[$action['RoomID'] . $action['Date']]['ActionList'][] = $action;
        }
        $result = array();
        foreach($roomList as $roomID=>$room) {
            $result[] = $room;
        }
        return $result;
    }
}